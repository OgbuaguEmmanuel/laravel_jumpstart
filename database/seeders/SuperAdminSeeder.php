<?php

namespace Database\Seeders;

use App\Enums\RoleTypeEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
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
       DB::beginTransaction();

        $superAdminData = [
            'first_name' => 'Super Admin',
            'last_name' => 'User',
            'email' => 'superadmin@laraveljumpstart.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Super@!Admin&25'), // Strong password for super admin
        ];

        $superAdmin = User::updateOrCreate(
            ['email' => $superAdminData['email']],
            $superAdminData
        );

        $this->command->info("Super Admin user '{$superAdmin->email}' created or found.");

        $superAdminRole = Role::findOrCreate(RoleTypeEnum::SuperAdmin);
        $this->command->info("Role '{$superAdminRole->name}' ensured to exist.");

        if (!$superAdmin->hasRole($superAdminRole)) {
            $superAdmin->assignRole($superAdminRole);
            $this->command->info("Assigned '{$superAdminRole->name}' role to {$superAdmin->email}.");
        } else {
            $this->command->info("{$superAdmin->email} already has '{$superAdminRole->name}' role.");
        }

        $allPermissions = Permission::all();

        $superAdminRole->syncPermissions($allPermissions);
        $this->command->info("All permissions synced to '{$superAdminRole->name}' role.");


        DB::commit();
        $this->command->info('Super Admin seeding completed successfully.');
    }
}
