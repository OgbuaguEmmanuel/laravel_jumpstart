<?php

namespace App\Actions;

use App\Enums\ActivityLogTypeEnum;
use App\Traits\AuthHelpers;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FALaravel\Google2FA;

class LoginUserUsing2FAAction
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
        $challengeKey = $data['2fa_challenge_key'];
        $userId = Cache::get('2fa_challenge:' . $challengeKey);
        $ipAddress = request()->ip();

        if (!$userId || !($user = $this->getUserByID($userId))) {
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy(null)
                ->withProperties([
                    'challenge_key_attempted' => $challengeKey,
                    'ip_address' => $ipAddress,
                    'reason' => '2FA challenge expired or invalid',
                ])
                ->log('2FA login failed: Invalid/Expired Challenge Key');

            throw ValidationException::withMessages([
                '2fa_challenge_key' => '2FA challenge expired or is invalid. Please log in again.',
            ])->status(400);
        }

        if (!$user->hasTwoFactorEnabled()) {
            Cache::forget('2fa_challenge:' . $challengeKey);
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => 'Two-factor authentication not enabled for account',
                ])
                ->log('2FA login failed: 2FA not enabled for user');
            throw ValidationException::withMessages([
                'code' => 'Two-factor authentication not enabled for this account.',
            ])->status(400);
        }

        $isValid = false;
        $authMethod = 'None';

        if (isset($data['code']) && !is_null($data['code']) && !is_null($user->two_factor_secret)) {
            $isValid = $this->google2fa->verifyKey($user->two_factor_secret, $data['code']);
            if ($isValid) {
                $authMethod = '2FA Code';
            }
        }

        if (!$isValid && isset($data['recovery_code']) && !is_null($data['recovery_code'])) {
            $recoveryCodes = $user->two_factor_recovery_codes;

            foreach ($recoveryCodes as $index => $code) {
                if ($data['recovery_code'] === $code) {
                    $isValid = true;
                    $authMethod = 'Recovery Code';

                    // Invalidate the used recovery code
                    unset($recoveryCodes[$index]);

                    $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)]);
                    $user->save();
                    break;
                }
            }
        }

        if (!$isValid) {
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'attempted_code_type' => (isset($data['code']) && !is_null($data['code'])) ? '2FA Code' : ((isset($data['recovery_code']) && !is_null($data['recovery_code'])) ? 'Recovery Code' : 'Unknown'),
                    'reason' => 'Invalid 2FA code or recovery code provided',
                ])
                ->log('2FA login failed: Invalid Code');
            throw ValidationException::withMessages([
                'code' => 'Invalid 2FA code or recovery code.',
            ])->status(400);
        }

        Cache::forget('2fa_challenge:' . $challengeKey);

        try {
            $token = $user->createToken('UserAuthToken')->accessToken;
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'authentication_method' => $authMethod,
                ])
                ->log('User successfully logged in with 2FA');
        } catch (Exception $e) {
            logger()->error('Login failed: ' . $e->getMessage(), [
                'email' => $data['email'],
                'code' => $e->getCode(),
            ]);
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'reason' => 'API Token creation failed after 2FA success',
                ])
                ->log('2FA login successful, but API token creation failed');

            throw new Exception("Something went wrong. Please contact support", 500);
        }

        $userDetails = $user->only('id', 'first_name', 'last_name', 'email');

        return [
            'token' => $token,
            'user' => $userDetails,
        ];
    }
}
