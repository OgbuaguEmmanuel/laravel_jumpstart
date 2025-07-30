<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityLogTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();
        Auth::forgetUser();

        activity()
            ->inLog(ActivityLogTypeEnum::Logout)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'token_revoked' => true,
            ])
            ->log('User logged out successfully');

        return ResponseBuilder::asSuccess()
            ->withHttpCode(200)
            ->withMessage('Logout Successful')
            ->build();

    }
}
