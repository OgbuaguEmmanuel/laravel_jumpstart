<?php

namespace App\Actions;

use App\Enums\ActivityLogTypeEnum;
use App\Traits\AuthHelpers;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Google2FA;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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

        if (!$user || !Hash::check($data['password'], $user->password)) {
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy(null)
                ->withProperties([
                    'email_attempted' => $data['email'],
                    'reason' => 'Invalid credentials provided',
                    'ip_address' => request()->ip()
                ])
                ->log('Login failed: Invalid Credentials');
            throw new Exception("Invalid Login Credential", 404);
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
                    'ip_address' => request()->ip()
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
            $token = $user->createToken('UserAuthToken')->accessToken;
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
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
                    'ip_address' => request()->ip(),
                    'code' => $e->getCode(),
                ])
                ->log('Login failed: API Token creation error');
            throw new Exception("Something went wrong. Please contact support", 500);
        }

        $userDetails = $user->only('id', 'first_name', 'last_name', 'email');

        return [
            'token' => $token,
            'user' => $userDetails,
            'message' => 'Login successful',
            'status' => Response::HTTP_OK
        ];
    }
}
