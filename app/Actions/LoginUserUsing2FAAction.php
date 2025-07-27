<?php

namespace App\Actions;

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

        if (!$userId || !($user = $this->getUserByID($userId))) {
            throw ValidationException::withMessages([
                '2fa_challenge_key' => '2FA challenge expired or is invalid. Please log in again.',
            ]);
        }

        if (!$user->hasTwoFactorEnabled()) {
            Cache::forget('2fa_challenge:' . $challengeKey);
            throw ValidationException::withMessages([
                'code' => 'Two-factor authentication not enabled for this account.',
            ]);
        }

        $isValid = false;

        if ($data['code'] && !is_null($user->two_factor_secret)) {
            $isValid = $this->google2fa->verifyKey($user->two_factor_secret, $data['code']);
        }

        if (!$isValid && !is_null($data['recovery_code'])) {
            $recoveryCodes = $user->two_factor_recovery_codes;

            foreach ($recoveryCodes as $index => $code) {
                if ($data['recovery_code'] === $code) {
                    $isValid = true;

                    // Invalidate the used recovery code
                    unset($recoveryCodes[$index]);

                    $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)]);
                    $user->save();
                    break;
                }
            }
        }

        if (!$isValid) {
            throw ValidationException::withMessages([
                'code' => 'Invalid 2FA code or recovery code.',
            ]);
        }

        Cache::forget('2fa_challenge:' . $challengeKey);

        try {
            $token = $user->createToken('UserAuthToken')->accessToken;
        } catch (Exception $e) {
            logger()->error('Login failed: ' . $e->getMessage(), [
                'email' => $data['email'],
                'code' => $e->getCode(),
            ]);
            throw new Exception("Something went wrong. Please contact support", 500);
        }

        $userDetails = $user->only('id', 'first_name', 'last_name', 'email');

        return [
            'token' => $token,
            'user' => $userDetails,
        ];
    }
}
