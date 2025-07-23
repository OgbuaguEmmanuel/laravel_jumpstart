<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Auth::forgetUser();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(200)
            ->withMessage('Logout Successful')
            ->build();

    }
}
