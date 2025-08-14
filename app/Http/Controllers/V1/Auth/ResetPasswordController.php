<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\ActivityLogTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Notifications\PasswordResetSuccessNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    /**
     * Get the broker to be used during password reset.
     */
    public function broker(): PasswordBroker
    {
        return Password::broker('users');
    }

    /**
     * Get the guard to be used during password reset.
     */
    protected function guard(): Guard|StatefulGuard
    {
        return Auth::guard('api');
    }

    /**
     * Get the response for a successful password reset.
     */
    protected function sendResetResponse(string $response): Response
    {
        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage(trans($response))
            ->build();
    }

    /**
     * @param  Request  $request
     */
    public function reset(ResetPasswordRequest $request): Response
    {
        $ipAddress = $request->ip();
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($ipAddress) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();

                $emailVerificationTriggered = false;
                if (! $user->hasVerifiedEmail()) {
                    $user->notify(new VerifyEmailNotification(config('frontend.email_verification_url')));
                    $emailVerificationTriggered = true;
                }

                // --- Log for Successful Password Reset ---
                activity()
                    ->inLog(ActivityLogTypeEnum::ResetPassword)
                    ->causedBy($user)
                    ->withProperties([
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip_address' => $ipAddress,
                        'email_verification_triggered' => $emailVerificationTriggered,
                        'action_type' => 'Password Reset Successful',
                    ])
                    ->log('User password reset successfully.');

                $user->notify(new PasswordResetSuccessNotification(config('frontend.login_url')));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResetResponse($status);
        } else {
            // --- Log for Failed Password Reset ---
            activity()
                ->inLog(ActivityLogTypeEnum::ResetPassword)
                ->causedBy(null)
                ->withProperties([
                    'email_attempted' => $request->email,
                    'ip_address' => $ipAddress,
                    'status_reason' => $status, // e.g., 'passwords.token', 'passwords.user', 'passwords.password'
                    'action_type' => 'Password Reset Failed',
                ])
                ->log('Password reset failed.');

            return $this->sendResetFailedResponse($status);
        }
    }

    /**
     * Get the response for a failed password reset.
     */
    protected function sendResetFailedResponse(string $response): Response
    {
        return ResponseBuilder::asError(400)
            ->withHttpCode(Response::HTTP_BAD_REQUEST)
            ->withMessage(trans($response))
            ->build();
    }
}
