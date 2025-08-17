<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function findByKey(string $key)
    {
        return cache()->rememberForever("setting_find_{$key}", function () use ($key) {
            return Setting::where('key', $key)->firstOrFail();
        });
    }

    public function get(string $key, $default = null)
    {
        return cache()->rememberForever("setting_get_{$key}", function () use ($key, $default) {
            return optional(Setting::where('key', $key)->first())->value ?? $default;
        });
    }

    public function set(string $key, $value)
    {
        $setting = Setting::updateOrCreate(['key' => $key], ['value' => $value]);

        cache()->forget("setting_get_{$key}");
        cache()->forget("setting_find_{$key}");
        cache()->forget('all_settings');

        return $setting;
    }

    public function all()
    {
        return cache()->rememberForever('all_settings', function () {
            return Setting::all();
        });
    }
}
