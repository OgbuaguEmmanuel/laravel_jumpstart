<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    /**
     * Mark the authenticated users' email address as verified.
     *
     * @param Request $request
     * @return Response
     */
    public function verify(Request $request): Response
    {
        if (!hash_equals((string) $request->id, (string) $request->user()->getKey())) {
            throw new AuthorizationException();
        }

        if (!hash_equals((string) $request->hash, sha1($request->user()->getEmailForVerification()))) {
            throw new AuthorizationException();
        }

        if ($request->user()->hasVerifiedEmail()) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('User email has previously being verified')
                ->build();
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('User email verified successfully!')
            ->build();
    }

    /**
     * Resend the email verification notification.
     *
     * @param Request $request
     * @return Response
     */
    public function resend(ResendVerificationRequest $request): Response
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('User already has a verified email')
                ->build();
        }

        $callbackUrl = request('callbackUrl', config('frontend.user.url'));

        $user->notify(new VerifyEmailNotification($callbackUrl));

        return ResponseBuilder::asSuccess(0)
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('We have sent you another email verification link')
            ->build();
    }
}
