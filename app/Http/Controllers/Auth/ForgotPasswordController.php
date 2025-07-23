<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($status)
            : $this->sendResetLinkFailedResponse($status);
    }

     /**
     * Get the response for a successful sent password reset link.
     *
     * @param string $response
     * @return Response
     */
    protected function sendResetLinkResponse(string $response): Response
    {
        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage(trans($response))
            ->build();
    }

    /**
     * Get the response for a failed send password reset.
     *
     * @param string $response
     * @return Response
     */
    protected function sendResetLinkFailedResponse(string $response): Response
    {
        return ResponseBuilder::asError(400)
            ->withHttpCode(Response::HTTP_BAD_REQUEST)
            ->withMessage(trans($response))
            ->build();
    }

}
