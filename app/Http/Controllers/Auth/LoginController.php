<?php

namespace App\Http\Controllers\Auth;

use App\Actions\LoginUserAction;
use App\Actions\LoginUserUsing2FA;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\TwoFactorLoginChallengeRequest;
use Exception;
use Illuminate\Validation\ValidationException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    public function login(LoginUserRequest $request, LoginUserAction $action)
    {
        try {
            $result = $action($request->validated());

            return ResponseBuilder::asSuccess()
                ->withHttpCode($result['status'])
                ->withData(collect($result)->except(['message', 'status'])->all())
                ->withMessage($result['message'])
                ->build();
        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            return ResponseBuilder::asError($statusCode)
                ->withHttpCode($statusCode)
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function challenge(TwoFactorLoginChallengeRequest $request, LoginUserUsing2FA $action)
    {
        try {
            $result = $action($request->validated());

        } catch (ValidationException $e) {
            return ResponseBuilder::asError(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->withHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            return ResponseBuilder::asError($statusCode)
                ->withHttpCode($statusCode)
                ->withMessage($e->getMessage())
                ->build();
        }

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('Logged in successfully with 2FA.')
            ->withData($result)
            ->build();
    }
}
