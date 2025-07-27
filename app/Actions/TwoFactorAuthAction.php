<?php

namespace App\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQrCode;

class TwoFactorAuthAction
{
    protected Google2FA $google2fa;
    protected Google2FAQrCode $google2faQrCode;

    public function __construct(Google2FA $google2fa, Google2FAQrCode $google2faQrCode)
    {
        $this->google2faQrCode = $google2faQrCode;
        $this->google2fa = $google2fa;
    }

    public function handleSetup()
    {
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                '2fa' => "Two-factor authentication is already enabled for this account."
            ])->status(400);
        }

        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill(['two_factor_secret' => $secret]);
        $user->save();

        $companyName = config('app.name');
        $accountName = $user->email;

        // Generate the otpauth:// URL for the authenticator app
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $companyName,
            $accountName,
            $secret
        );

        // Generate the QR code SVG
        $qrCodeSvg = $this->google2faQrCode->getQrCodeInline(
            $companyName,
            $accountName,
            $secret,
        );

        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'qrCodeSvg' => $qrCodeSvg
        ];
    }

    public function handleEnable(array $data)
    {
        $user = Auth::user();

        if (!$user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                '2fa' => "2FA setup not initiated or already enabled."
            ])->status(400);
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $data['code']);

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => 'Invalid 2FA code. Please try again.'
            ])->status(400);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            'two_factor_enabled_at' => now(),
        ]);
        $user->save();

        return [
            'recovery_codes' => $user->two_factor_recovery_codes
        ];
    }

    public function handleDisable(array $data)
    {
        $user = Auth::user();

        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid password.'
            ])->status(422);
        }

        if (!$user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                '2fa' => 'Two-factor authentication is not enabled for this account.'
            ])->status(400);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null
        ]);
        $user->save();

    }

    public function handleRecoveryCode()
    {
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                '2fa' => 'Two-factor authentication is not enabled for this account.'
            ])->status(400);
        }

        $newRecoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $newRecoveryCodes]);
        $user->save();

        return [
            'recovery_codes' => $newRecoveryCodes
        ];

    }

    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) { // Generate 8 codes
            // Generate a random 10-character alphanumeric string, split by hyphen
            $codes[] = implode('-', str_split(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10), 5));
        }
        return $codes;
    }
}
