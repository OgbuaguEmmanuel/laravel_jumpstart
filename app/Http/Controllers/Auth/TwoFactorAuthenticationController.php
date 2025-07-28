<?php

namespace App\Http\Controllers\Auth;

use App\Actions\TwoFactorAuthAction;
use App\Enums\ActivityLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactor\Disable2FARequest;
use App\Http\Requests\TwoFactor\Verify2FARequest;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthenticationController extends Controller
{
    public function setup(TwoFactorAuthAction $action)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();
        try {
            $data = $action->handleSetup();

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withMessage('2FA setup initiated. Scan the QR code.')
                ->withData([
                    'secret' => $data['secret'],
                    'qr_code_svg' => $data['qrCodeSvg'],
                    'qr_code_url' => $data['qrCodeUrl'],
                ])
                ->build();
        } catch (ValidationException $e) {
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch(Exception $e) {
            logger()->error('2FA setup unexpected error: ' . $e->getMessage(), ['exception' => $e]);
            activity()
                ->inLog(ActivityLogType::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'action_type' => '2FA Setup Failed: Unexpected Error',
                ])
                ->log('2FA setup failed due to an unexpected error.');
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }

    public function enable(Verify2FARequest $request, TwoFactorAuthAction $action)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        try {
            $data = $action->handleEnable($request->validated());

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withMessage('Two-factor authentication enabled successfully.')
                ->withData([
                    'recovery_codes' => $data['recovery_codes'],
                ])
                ->build();
        } catch (ValidationException $e) {
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch(Exception $e) {
            logger()->error('2FA enable unexpected error: ' . $e->getMessage(), ['exception' => $e]);

            activity()
                ->inLog(ActivityLogType::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'action_type' => '2FA Enable Failed: Unexpected Error',
                ])
                ->log('2FA enablement failed due to an unexpected error.');
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }

    public function disable(Disable2FARequest $request, TwoFactorAuthAction $action)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        try {
            $action->handleDisable($request->validated());

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withMessage('Two-factor authentication disabled successfully.')
                ->build();
        } catch (ValidationException $e) {
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch(Exception $e) {
            logger()->error('2FA disable unexpected error: ' . $e->getMessage(), ['exception' => $e]);

            activity()
                ->inLog(ActivityLogType::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'action_type' => '2FA Disable Failed: Unexpected Error',
                ])
                ->log('2FA disablement failed due to an unexpected error.');
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }

    public function generateNewRecoveryCodes(TwoFactorAuthAction $action)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        try {
            $data = $action->handleRecoveryCode();

            return ResponseBuilder::asSuccess()
                ->withHttpCode(Response::HTTP_OK)
                ->withMessage('New recovery codes generated successfully.')
                ->withData([
                    'recovery_codes' => $data['recovery_codes']
                ])
                ->build();
        } catch (ValidationException $e) {
            return ResponseBuilder::asError($e->status)
                ->withHttpCode($e->status)
                ->withMessage($e->getMessage())
                ->withData($e->errors())
                ->build();
        } catch(Exception $e) {
            logger()->error('2FA recovery code generation unexpected error: ' . $e->getMessage(), ['exception' => $e]);

            activity()
                ->inLog(ActivityLogType::TwoFactorAuth)
                ->causedBy($user)
                ->withProperties([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'error_message' => $e->getMessage(),
                    'action_type' => '2FA Recovery Code Generation Failed: Unexpected Error',
                ])
                ->log('2FA recovery code generation failed due to an unexpected error.');

            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }
}
