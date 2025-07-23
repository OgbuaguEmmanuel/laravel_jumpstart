<?php

namespace App\Http\Controllers\Auth;

use App\Actions\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LoginController extends Controller
{
    public function login(LoginUserRequest $request, LoginUserAction $action)
    {
        $data = $action($request->validated());

        return ResponseBuilder::asSuccess()
            ->withData(['data'=> $data])
            ->withMessage('Login successful')
            ->build();
    }
}
