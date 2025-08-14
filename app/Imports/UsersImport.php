<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Spatie\Permission\Models\Role;

class UsersImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    protected $importedBy;
    protected $currentRow = 1;

    public function __construct($importedBy)
    {
        $this->importedBy = $importedBy;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->currentRow++;
        $tempPassword = Str::random(12);

        if (! empty($row['role'])) {
            $roleNames = array_map('trim', explode(',', $row['role']));

            $roles = Role::whereIn('name', $roleNames)->pluck('name');

            if ($roles->count() !== count($roleNames)) {
                $this->failRow($row, 'role', 'One or more roles do not exist');
                return null;
            }
        }

        $user = User::updateOrCreate(
            [
                'email' => $row['email'],
            ],
            [
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'password' => Hash::make($tempPassword),
                'force_password_reset' => true,
                'created_by' => $this->importedBy,
            ]
        );

        if (! empty($row['role'])) {
            $user->syncRoles($roles);
        }

        return $user;
    }

    public function rules(): array
    {
        return [
            '*.first_name' => 'required|string|max:255',
            '*.last_name' => 'required|string|max:255',
            '*.email' => 'required|email',
            '*.role' => 'nullable|string',
        ];
    }

    protected function failRow(array $row, string $attribute, string $error)
    {
        $this->onFailure(new \Maatwebsite\Excel\Validators\Failure(
            $this->currentRow,
            $attribute,
            [$error],
            $row
        ));
    }
}
