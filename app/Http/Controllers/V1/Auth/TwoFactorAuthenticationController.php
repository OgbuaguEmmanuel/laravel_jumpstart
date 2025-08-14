<?php

namespace App\Http\Controllers\V1\Auth;

use App\Actions\TwoFactorAuthAction;
use App\Helpers\APIExceptionHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactor\Disable2FARequest;
use App\Http\Requests\TwoFactor\Verify2FARequest;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TwoFactorAuthenticationController extends Controller
{
    protected APIExceptionHandler $apiExceptionHandler;

    public function __construct(APIExceptionHandler $apiExceptionHandler)
    {
        $this->apiExceptionHandler = $apiExceptionHandler;
    }

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
        } catch (Throwable $th) {
            logger()->error('2FA setup unexpected error: '.$th->getMessage(), ['exception' => $th]);
            $this->apiExceptionHandler->handle(request(), $th);
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
        } catch (Throwable $th) {
            logger()->error('2FA enable unexpected error: '.$th->getMessage(), ['exception' => $th]);
            $this->apiExceptionHandler->handle(request(), $th);
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
        } catch (Throwable $th) {
            logger()->error('2FA disable unexpected error: '.$th->getMessage(), ['exception' => $th]);
            $this->apiExceptionHandler->handle(request(), $th);
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
                    'recovery_codes' => $data['recovery_codes'],
                ])
                ->build();
        } catch (Throwable $th) {
            logger()->error('2FA recovery code generation unexpected error: '.$th->getMessage(), ['exception' => $th]);
            $this->apiExceptionHandler->handle(request(), $th);

        }
    }
}
