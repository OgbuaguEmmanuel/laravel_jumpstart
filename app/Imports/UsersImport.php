<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Spatie\Permission\Models\Role;

class UsersImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    protected $importedBy;

    protected $batch = [];

    protected $roleAssignments = [];

    protected $batchSize = 1000;

    protected $failedRows = [];

    protected $newUserIds = [];

    public function __construct($importedBy)
    {
        $this->importedBy = $importedBy;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $roles = $this->validateRoles($row, $index);

            $tempPassword = Str::random(12);

            $this->batch[] = [
                'user' => [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'password' => Hash::make($tempPassword),
                    'force_password_reset' => true,
                    'created_by' => $this->importedBy,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'roles' => array_values($roles),
            ];

            if (count($this->batch) >= $this->batchSize) {
                $this->insertBatch();
            }
        }

        // Insert remaining
        $this->insertBatch();
    }

    protected function insertBatch()
    {
        if (empty($this->batch)) {
            return;
        }

        $userRows = array_column($this->batch, 'user');
        DB::table('users')->insert($userRows);

        $startId = DB::getPdo()->lastInsertId();
        $userIds = range($startId, $startId + count($userRows) - 1);
        $this->newUserIds = array_merge($this->newUserIds, $userIds);

        // Roles
        foreach ($this->batch as $i => $entry) {
            foreach ($entry['roles'] as $roleId) {
                $this->roleAssignments[] = [
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $userIds[$i],
                ];
            }
        }

        if (! empty($this->roleAssignments)) {
            DB::table('model_has_roles')->insert($this->roleAssignments);
            $this->roleAssignments = [];
        }

        $this->notifyUsers($userIds);

        $this->batch = [];
    }

    public function rules(): array
    {
        return [
            '*.first_name' => 'required|string|max:255',
            '*.last_name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:users,email',
            '*.role' => 'nullable|string',
        ];
    }

    protected function failRow(int $rowNumber, string $attribute, string $error, array $row)
    {
        $failure = new Failure($rowNumber, $attribute, [$error], $row);
        $this->onFailure($failure);

        $this->failedRows[] = [
            'row' => $rowNumber,
            'attribute' => $attribute,
            'error' => $error,
            'data' => $row,
        ];
    }

    protected function validateRoles($row, $index)
    {
        $row = array_map('trim', $row->toArray());

        $roles = [];
        if (! empty($row['role'])) {
            $roleNames = explode(',', $row['role']);
            $roleNames = array_map('trim', $roleNames);

            $roles = Role::whereIn('name', $roleNames)->pluck('id', 'name')->toArray();
            if (count($roles) !== count($roleNames)) {
                $this->failRow($index + 2, 'role', 'One or more roles do not exist', $row);

                return [];
            }
        }

        return $roles;
    }

    protected function notifyUsers($userIds)
    {
        dispatch(function () use ($userIds) {
            $users = User::whereIn('id', $userIds)->get();
            foreach ($users as $user) {
                $user->createdEventActions();
            }
        })->onQueue('emails');
    }
}
