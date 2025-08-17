<?php

namespace App\Http\Controllers\V1\Admin;

use App\Facades\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddSettingsRequest;
use App\Http\Requests\BulkSettingsRequest;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class SettingsController extends Controller
{
    use AuthorizesRequests;

    public function show(string $key)
    {
        $this->authorize('view', Setting::class);

        return ResponseBuilder::asSuccess()
            ->withData(Settings::get($key))
            ->withMessage('Here we go!')
            ->build();
    }

    public function index()
    {
        $this->authorize('viewAny', Setting::class);

        return ResponseBuilder::asSuccess()
            ->withData(Settings::all())
            ->withMessage('Here we go!')
            ->build();
    }

    public function storeOrUpdate(AddSettingsRequest $request)
    {
        $this->authorize('storeOrUpdate', Setting::class);

        $setting = Settings::set($request->validated('key'), $request->validated('value'));

        $message = $setting->wasRecentlyCreated ? 'Setting created successfully.' : 'Setting updated successfully.';

        return ResponseBuilder::asSuccess()
            ->withMessage($message)
            ->withData($setting)
            ->build();
    }

    public function bulkSetOrUpdate(BulkSettingsRequest $request)
    {
        $this->authorize('storeOrUpdate', Setting::class);

        DB::transaction(function () use ($request) {
            foreach ($request->validated()['settings'] as $setting) {
                Settings::set($setting['key'], $setting['value']);
            }
        });

        return ResponseBuilder::asSuccess()
            ->withMessage('Settings saved successfully.')
            ->build();
    }
}
