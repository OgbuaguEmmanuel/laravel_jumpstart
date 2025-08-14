<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return User::with('roles')
            ->get()
            ->map(function ($user) {
                return [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => optional($user->roles->first())->name,
                    'created_at' => $user->created_at->toDateTimeString(),
                ];
            });
    }

    public function headings(): array
    {
        return ['First Name', 'Last Name', 'Email', 'Role', 'Created At'];
    }
}
