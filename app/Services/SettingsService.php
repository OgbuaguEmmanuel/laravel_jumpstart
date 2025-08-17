<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key)
    {
        return cache()->rememberForever("setting_{$key}", function () use ($key) {
            return Setting::where('key', $key)->firstOrFail();
        });
    }

    public function set(string $key, $value)
    {
        $setting = Setting::updateOrCreate(['key' => $key], ['value' => $value]);

        cache()->forget("setting_{$key}");
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
