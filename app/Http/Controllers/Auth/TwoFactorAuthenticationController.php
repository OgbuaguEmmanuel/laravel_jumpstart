<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disable2FARequest;
use App\Http\Requests\Verify2FARequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQrCode;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthenticationController extends Controller
{
    protected Google2FA $google2fa;
    protected Google2FAQrCode $google2faQrCode;

    public function __construct(Google2FA $google2fa, Google2FAQrCode $google2faQrCode)
    {
        $this->google2faQrCode = $google2faQrCode;
        $this->google2fa = $google2fa;
    }

    public function setup()
    {
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('Two-factor authentication is already enabled for this account.')
                ->build();
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

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('2FA setup initiated. Scan the QR code.')
            ->withData([
                'secret' => $secret,
                'qr_code_svg' => $qrCodeSvg,
                'qr_code_url' => $qrCodeUrl,
            ])
            ->build();
    }

    public function enable(Verify2FARequest $request)
    {
        $user = Auth::user();

        if (!$user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('2FA setup not initiated or already enabled.')
                ->build();
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $request->validated('code'));

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => 'Invalid 2FA code. Please try again.',
            ]);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            'two_factor_enabled_at' => now(),
        ]);
        $user->save();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('Two-factor authentication enabled successfully.')
            ->withData([
                'recovery_codes' => $user->two_factor_recovery_codes,
            ])
            ->build();
    }

    public function disable(Disable2FARequest $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid password.',
            ]);
        }

        if (!$user->hasTwoFactorEnabled()) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('Two-factor authentication is not enabled for this account.')
                ->build();
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null
        ]);
        $user->save();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('Two-factor authentication disabled successfully.')
            ->build();
    }

    public function generateNewRecoveryCodes()
    {
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return ResponseBuilder::asError(400)
                ->withHttpCode(Response::HTTP_BAD_REQUEST)
                ->withMessage('Two-factor authentication is not enabled for this account.')
                ->build();
        }

        $newRecoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $newRecoveryCodes]);
        $user->save();

        return ResponseBuilder::asSuccess()
            ->withHttpCode(Response::HTTP_OK)
            ->withMessage('New recovery codes generated successfully.')
            ->withData([
                'recovery_codes' => $newRecoveryCodes,
            ])
            ->build();
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
