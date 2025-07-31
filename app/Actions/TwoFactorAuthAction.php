<?php

namespace App\Actions;

use App\Enums\ActivityLogTypeEnum;
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
        $ipAddress = request()->ip();

        if ($user->hasTwoFactorEnabled()) {
            activity()
                ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => '2FA already enabled',
                    'action_type' => '2FA Setup Attempt: Already Enabled',
                ])
                ->log('2FA setup attempted but already enabled for user.');

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

        activity()
            ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'action_type' => '2FA Setup Initiated',
            ])
            ->log('User initiated 2FA setup (secret generated).');

        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'qrCodeSvg' => $qrCodeSvg
        ];
    }

    public function handleEnable(array $data)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        if (!$user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            activity()
                ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => '2FA setup not initiated or already enabled',
                    'action_type' => '2FA Enable Attempt: Invalid State',
                ])
                ->log('2FA enable attempted but user is in invalid state.');

            throw ValidationException::withMessages([
                '2fa' => "2FA setup not initiated or already enabled."
            ])->status(400);
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $data['code']);

        if (!$valid) {
            activity()
                ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'attempted_code' => $data['code'],
                    'reason' => 'Invalid 2FA code provided during enablement',
                    'action_type' => '2FA Enable Failed: Invalid Code',
                ])
                ->log('2FA enable failed: Invalid verification code.');

            throw ValidationException::withMessages([
                'code' => 'Invalid 2FA code. Please try again.'
            ])->status(400);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            'two_factor_enabled_at' => now(),
        ]);
        $user->save();

        activity()
            ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'action_type' => '2FA Enabled Successfully',
            ])
            ->log('User successfully enabled 2FA.');

        return [
            'recovery_codes' => $user->two_factor_recovery_codes
        ];
    }

    public function handleDisable(array $data)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        if (!Hash::check($data['password'], $user->password)) {
            activity()
                ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => 'Invalid password provided for 2FA disable',
                    'action_type' => '2FA Disable Failed: Invalid Password',
                ])
                ->log('2FA disable failed: Invalid password.');

            throw ValidationException::withMessages([
                'password' => 'Invalid password.'
            ])->status(422);
        }

        if (!$user->hasTwoFactorEnabled()) {
            activity()
                ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => '2FA not enabled for this account',
                    'action_type' => '2FA Disable Attempt: Not Enabled',
                ])
                ->log('2FA disable attempted but not enabled for user.');
                
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

        activity()
            ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'action_type' => '2FA Disabled Successfully',
            ])
            ->log('User successfully disabled 2FA.');

    }

    public function handleRecoveryCode()
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        if (!$user->hasTwoFactorEnabled()) {
            activity()
                ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'reason' => '2FA not enabled for this account',
                    'action_type' => '2FA Recovery Code Generation Attempt: Not Enabled',
                ])
                ->log('2FA recovery code generation attempted but 2FA not enabled.');

            throw ValidationException::withMessages([
                '2fa' => 'Two-factor authentication is not enabled for this account.'
            ])->status(400);
        }

        $newRecoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $newRecoveryCodes]);
        $user->save();

        activity()
            ->inLog(ActivityLogTypeEnum::TwoFactorAuth)
            ->causedBy($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'action_type' => '2FA Recovery Codes Generated',
            ])
            ->log('User generated new 2FA recovery codes.');

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
