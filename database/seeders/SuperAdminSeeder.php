<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $items = [
            [
                'first_name' => 'Super Admin',
                'last_name' => 'User',
                'email' => 'superadmin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('Super@!Admin&25'),
                'phone_number' => '+2348137567890',
            ]
        ];

        DB::beginTransaction();
        foreach ($items as $item) {
            $superAdmin = User::firstOrNew(
                ['email' => $item['email']],
                [
                    'first_name' => $item['first_name'],
                    'last_name' => $item['last_name'],
                    'email' => $item['email'],
                    'email_verified_at' => $item['email_verified_at'],
                    'phone_number' => $item['phone_number'],
                    'password' => $item['password'],
                ],
            );

            $superAdmin->save();
        }

        $role = new Role();
        $role->name = 'super-admin';
        $role->guard_name = 'users';
        $role->save();

        $superAdmin->assignRole('super-admin');

        DB::commit();
    }
}
