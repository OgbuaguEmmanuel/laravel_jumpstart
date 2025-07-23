<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return ResponseBuilder::asSuccess()
            ->withMessage('Logout Successful')
            ->build();

    }
}
