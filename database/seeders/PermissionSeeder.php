<?php

namespace Database\Seeders;

use App\Enums\PermissionTypeEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PermissionTypeEnum::getValues() as $permissionName) {
            Permission::findOrCreate($permissionName);

            $this->command->info("Permission '{$permissionName}' created or found.");
        }

        $this->command->info('All permissions from PermissionTypeEnum have been seeded successfully.');
    }
}
