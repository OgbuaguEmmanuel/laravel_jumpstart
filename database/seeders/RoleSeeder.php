<?php

namespace Database\Seeders;

use App\Enums\RoleTypeEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RoleTypeEnum::getValues() as $roleName) {
            Role::findOrCreate($roleName);

            $this->command->info("Role '{$roleName}' created or found.");
        }

        $this->command->info('All roles from RoleTypeEnum have been seeded successfully.');
    }
}
