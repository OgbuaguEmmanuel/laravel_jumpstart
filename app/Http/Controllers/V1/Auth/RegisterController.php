<?php

namespace App\Http\Controllers\V1\Auth;

use App\Actions\CreateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateUserRequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class RegisterController extends Controller
{
    public function register(CreateUserRequest $request, CreateUserAction $action)
    {
        $file = null;
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
        }

        $action->handle($request->validated(), $file);

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('User registered successfully.')
            ->build();
    }

}
