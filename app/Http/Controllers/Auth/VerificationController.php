<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityLogTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $ipAddress = $request->ip();
        $user = $request->user();

        if (!hash_equals((string) $request->id, (string) $user->getKey())) {
            $this->logVerificationFailure($user, $ipAddress, 'User ID mismatch in verification link', 'Email Verification Failed: Unauthorized ID');
            throw new AuthorizationException('Invalid user ID in verification link.');
        }

        if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            $this->logVerificationFailure($user, $ipAddress, 'Verification hash mismatch', 'Email Verification Failed: Invalid Hash');
            throw new AuthorizationException('Invalid verification hash.');
        }

        if ($user->hasVerifiedEmail()) {
            $this->logVerificationFailure($user, $ipAddress, 'Email already verified', 'Email Verification Attempt: Already Verified');
            throw ValidationException::withMessages([
                'email' => 'User email has already been verified.'
            ])->status(Response::HTTP_CONFLICT);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->activateAccount();
            $this->logVerificationFailure($user, $ipAddress, 'Email Verified Successfully', 'Email Verified Successfully.Login to start using the platform');
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
        $ipAddress = $request->ip();

        if ($user->hasVerifiedEmail()) {
            $this->logVerificationFailure($user, $ipAddress, 'Resend attempted for already verified email', 'Email Verification Resend Attempt: Already Verified');
            throw ValidationException::withMessages([
                'email' => 'User already has a verified email.'
            ])->status(Response::HTTP_CONFLICT);
        }

        $callbackUrl = request('callbackUrl', config('frontend.url'));
        $user->sendEmailVerificationNotification($callbackUrl);

        activity()
            ->inLog(ActivityLogTypeEnum::VerifyEmail)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'callback_url' => $callbackUrl,
                'action_type' => 'Email Verification Link Resent',
            ])
            ->log('Email verification link resent successfully.');

        return ResponseBuilder::asSuccess(0)
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('We have sent you another email verification link')
            ->build();
    }

    private function logVerificationFailure($user, string $ipAddress, string $reason, string $actionType): void
    {
        activity()
            ->inLog(ActivityLogTypeEnum::VerifyEmail)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'reason' => $reason,
                'action_type' => $actionType,
            ])
            ->log($reason);
    }
}
