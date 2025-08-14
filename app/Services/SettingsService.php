<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key, $default = null)
    {
        return cache()->rememberForever("setting_{$key}", function () use ($key, $default) {
            return optional(Setting::where('key', $key)->first())->value ?? $default;
        });
    }

    public function set(string $key, $value)
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        cache()->forget("setting_{$key}");
    }

    public function all(): array
    {
        return cache()->rememberForever('all_settings', function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }
}
