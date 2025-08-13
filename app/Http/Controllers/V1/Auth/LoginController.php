<?php

namespace App\Http\Controllers\V1\Auth;

use App\Actions\LoginUserAction;
use App\Actions\LoginUserUsing2FAAction;
use App\Helpers\APIExceptionHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\TwoFactor\TwoFactorLoginChallengeRequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LoginController extends Controller
{
    protected APIExceptionHandler $apiExceptionHandler;

    public function __construct(APIExceptionHandler $apiExceptionHandler)
    {
        $this->apiExceptionHandler = $apiExceptionHandler;
    }

    public function login(LoginUserRequest $request, LoginUserAction $action)
    {
        try {
            $result = $action->handle($request->validated());

            return ResponseBuilder::asSuccess()
                ->withHttpCode($result['status'])
                ->withData(collect($result)->except(['message', 'status'])->all())
                ->withMessage($result['message'])
                ->build();
        } catch (Throwable $th) {
            return $this->apiExceptionHandler->handle($request, $th);
        }
    }

    public function challenge(TwoFactorLoginChallengeRequest $request, LoginUserUsing2FAAction $action)
    {
        try {
            $result = $action->handle($request->validated());

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withMessage('Logged in successfully with 2FA.')
                ->withData($result)
                ->build();
        } catch (Throwable $th) {
            return $this->apiExceptionHandler->handle($request, $th);
        }
    }
}
