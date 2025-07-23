<?php

namespace App\Http\Controllers\Auth;

use App\Actions\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use Exception;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class LoginController extends Controller
{
    public function login(LoginUserRequest $request, LoginUserAction $action)
    {
        try {
            $data = $action($request->validated());

            return ResponseBuilder::asSuccess()
                ->withHttpCode(200)
                ->withData($data)
                ->withMessage('Login successful')
                ->build();
        } catch(Exception $e) {
            return ResponseBuilder::asError($e->getCode())
                ->withHttpCode($e->getCode())
                ->withMessage($e->getMessage())
                ->build();
        }
    }
}
