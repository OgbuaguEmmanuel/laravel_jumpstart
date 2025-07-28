<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ActivityLogType;
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
        $ipAddress = $request->ip();
        $user = $request->user();

        if (!hash_equals((string) $request->id, (string) $user->getKey())) {
            activity()
                ->inLog(ActivityLogType::VerifyEmail)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => 'User ID mismatch in verification link',
                    'action_type' => 'Email Verification Failed: Unauthorized ID',
                ])
                ->log('Email verification failed: User ID mismatch.');

            throw new AuthorizationException('Invalid user ID in verification link.');
        }

        if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            activity()
                ->inLog(ActivityLogType::VerifyEmail)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => 'Verification hash mismatch',
                    'action_type' => 'Email Verification Failed: Invalid Hash',
                ])
                ->log('Email verification failed: Invalid hash.');

            throw new AuthorizationException('Invalid verification hash.');
        }

        if ($user->hasVerifiedEmail()) {
            activity()
                ->inLog(ActivityLogType::VerifyEmail)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => 'Email already verified',
                    'action_type' => 'Email Verification Attempt: Already Verified',
                ])
                ->log('Email verification attempted but email already verified.');

            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('User email has previously being verified')
                ->build();
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            activity()
                ->inLog(ActivityLogType::VerifyEmail)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'action_type' => 'Email Verified Successfully',
                ])
                ->log('User email verified successfully.');
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
            activity()
                ->inLog(ActivityLogType::VerifyEmail)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => 'Resend attempted for already verified email',
                    'action_type' => 'Email Verification Resend Attempt: Already Verified',
                ])
                ->log('Email verification resend attempted but email already verified.');

            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('User already has a verified email')
                ->build();
        }

        $callbackUrl = request('callbackUrl', config('frontend.user.url'));

        $user->notify(new VerifyEmailNotification($callbackUrl));

        activity()
            ->inLog(ActivityLogType::VerifyEmail)
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
}
