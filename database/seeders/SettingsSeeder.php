<?php

namespace Database\Seeders;

use App\Facades\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Settings::set('two_factor_enabled', config('services.2FA.enabled'));
        Settings::set('default_language', 'en');
        Settings::set('pagination_size', config('services.pagination.size'));
    }
}
