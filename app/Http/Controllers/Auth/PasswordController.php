<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class PasswordController extends Controller
{
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->validated('current_password'), $user->password)) {
            activity()
                ->inLog(ActivityLogType::ChangePassword)
                ->causedBy($user)
                ->log('Current password is incorrect');

            return ResponseBuilder::asError(422)
                ->withHttpCode(422)
                ->withMessage('Current password is incorrect')
                ->build();
        }

        $user->password = Hash::make($request->validated('new_password'));
        $user->save();

        activity()
            ->inLog(ActivityLogType::ChangePassword)
            ->causedBy($user)
            ->log('Password changed successfully');
        return ResponseBuilder::asSuccess()
            ->withHttpCode(200)
            ->withMessage('Password changed successfully')
            ->build();
    }

}
