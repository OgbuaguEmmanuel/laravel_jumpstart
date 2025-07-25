<?php

namespace App\Http\Controllers\Auth;

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
            return ResponseBuilder::asError(422)
                ->withHttpCode(422)
                ->withMessage('Current password is incorrect')
                ->build();
        }
        $user->password = Hash::make($request->validated('new_password'));
        $user->save();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(200)
            ->withMessage('Password changed successfully')
            ->build();
    }

}
