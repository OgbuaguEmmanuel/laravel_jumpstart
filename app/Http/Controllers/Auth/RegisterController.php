<?php

namespace App\Http\Controllers\Auth;

use App\Actions\CreateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateUserRequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class RegisterController extends Controller
{
    public function register(CreateUserRequest $request, CreateUserAction $action)
    {
        $action->handle($request->validated());

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('User registered successfully.')
            ->build();
    }

}
