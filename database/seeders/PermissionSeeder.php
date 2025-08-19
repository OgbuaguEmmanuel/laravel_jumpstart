<?php

namespace Database\Seeders;

use App\Enums\PermissionTypeEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
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

        Cache::forget('permissions.all');
        Cache::rememberForever('permissions.all', function () {
            return Permission::all();
        });

        $this->command->info('All permissions from PermissionTypeEnum have been seeded successfully.');
    }
}
