<?php

namespace App\Http\Controllers\Auth;

use App\Actions\TwoFactorAuthAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disable2FARequest;
use App\Http\Requests\Verify2FARequest;
use Exception;
use Illuminate\Validation\ValidationException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthenticationController extends Controller
{
    public function setup(TwoFactorAuthAction $action)
    {
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
            logger($e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }

    public function enable(Verify2FARequest $request, TwoFactorAuthAction $action)
    {
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
            logger($e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }

    public function disable(Disable2FARequest $request, TwoFactorAuthAction $action)
    {
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
            logger($e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }

    public function generateNewRecoveryCodes(TwoFactorAuthAction $action)
    {
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
            logger($e->getMessage());
            return ResponseBuilder::asError(500)
                ->withHttpCode(500)
                ->withMessage('Something went wrong. Contact support.')
                ->build();
        }
    }
}
