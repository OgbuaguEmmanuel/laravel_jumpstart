<?php

namespace App\Actions;

use App\Enums\ActivityLogTypeEnum;
use App\Notifications\LoginAlertNotification;
use App\Notifications\VerifyEmailNotification;
use App\Traits\AuthHelpers;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Google2FA;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Events\Failed;

class LoginUserAction
{
    use AuthHelpers;

    protected Google2FA $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    /**
     * Create a new class instance.
     */
    public function handle(array $data)
    {
        $user = $this->getUserByEmail($data['email']);
        $ipAddress = request()->ip();

        if ($user && $user->isLocked()) {
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email_attempted' => $data['email'],
                    'reason' => 'Login failed: Account is locked',
                    'ip_address' => $ipAddress
                ])
                ->log('Login failed: Account locked');

            throw ValidationException::withMessages([
                'email' => [
                    'Your account has been locked. Please try again later or contact support.',
                ],
            ])->status(423); // 423 Locked status code
        }

        if (!$user || !Hash::check($data['password'], $user->password)) {
            try {
                event(new Failed('api', $user, ['email' => $data['email'], 'password' => $data['password']]));
            } catch(Exception $e) {
                logger('from event '. $e->getMessage());
                throw $e;
            }
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy(null)
                ->withProperties([
                    'email_attempted' => $data['email'],
                    'reason' => 'Invalid credentials provided',
                    'ip_address' => $ipAddress
                ])
                ->log('Login failed: Invalid Credentials');
            throw ValidationException::withMessages([
                'email' => ["Invalid Login Credential"],
            ])->status(404);
        }

        if ($user && $user->force_password_reset) {
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email_attempted' => $data['email'],
                    'reason' => 'Login failed: You must reset password to continue',
                    'ip_address' => $ipAddress
                ])
                ->log('Login failed: Reset password to continue');

            throw ValidationException::withMessages([
                'email' => [
                    'You must reset password to continue. Reset your password.',
                ],
            ])->status(403);
        }

        if ($user->hasTwoFactorEnabled()) {
            $challengeKey = Str::uuid()->toString();
            Cache::put('2fa_challenge:' . $challengeKey, $user->id, now()->addMinutes(5));

            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email' => $user->email,
                    '2fa_challenge_key' => $challengeKey,
                    'reason' => 'Two-factor authentication required',
                    'ip_address' => $ipAddress
                ])
                ->log('Login attempt: 2FA required');

            return [
                'requires_2fa' => true,
                '2fa_challenge_key' => $challengeKey,
                'message' => 'Two-factor authentication required. Please provide your 2FA code.',
                'status' => Response::HTTP_ACCEPTED
            ];
        }

        try {
            $token = $user->createToken('UserAuthToken')->plainTextToken;

            if (! $user->hasVerifiedEmail()) {
                $callbackUrl = request('callbackUrl', config('frontend.url'));
                $user->notify(new VerifyEmailNotification($callbackUrl));

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
            }

            $user->resetFailedAttempts();

            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'token_created' => true
                ])
                ->log('User logged in successfully');
        } catch (Exception $e) {
            logger()->error('Login failed: ' . $e->getMessage(), [
                'email' => $data['email'],
                'code' => $e->getCode(),
            ]);
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email' => $user->email,
                    'error_message' => $e->getMessage(),
                    'ip_address' => $ipAddress,
                    'code' => $e->getCode(),
                ])
                ->log('Login failed: API Token creation error');

            throw new Exception("Something went wrong. Please contact support", 500);
        }

        $userDetails = $user->only('id', 'first_name', 'last_name', 'email');
        $userDetails['profile_picture_url'] = $user->profilePicture();
        $userDetails['roles'] = $user->roles()->select('id', 'name')
            ->with(['permissions:id,name'])->get();

        $userDetails['directPermissions'] = $user->permissions()->select('id', 'name')->get();

        if (! $user->hasVerifiedEmail()) {
            return [
                'token' => $token,
                'user' => $userDetails,
                'message' => 'Check your email and verify your email address',
                'status' => Response::HTTP_PARTIAL_CONTENT
            ];
        } else {
            $user->notify(new LoginAlertNotification(
                'Email/Password', request()->ip(), request()->userAgent())
            );

            return [
                'token' => $token,
                'user' => $userDetails,
                'message' => 'Login successful',
                'status' => Response::HTTP_OK
            ];
        }
    }
}
