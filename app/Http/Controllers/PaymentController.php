<?php

namespace App\Http\Controllers;

use App\DTOs\PaymentPayload;
use App\Enums\PaymentCurrencyEnum;
use App\Enums\PaymentPurposeEnum;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\WebhookEvent;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(public PaymentGatewayInterface $gateway) {}

    public function initialize(InitiatePaymentRequest $request)
    {
        try {
            $user = Auth::user();
            $dto = new PaymentPayload(
                $user->email,
                $request->validatedAmount(),
                $request->validated('currency', PaymentCurrencyEnum::NARIA),
                $request->validated('callbackUrl'),
                [
                    'user_id' => $user->id,
                    'transactionable_id' => $user->id,
                    'transactionable_type' => get_class($user),
                    'email' => $user->email,
                    'first_name' =>$user->first_name,
                    'last_name' => $user->last_name,
                    'purpose' => PaymentPurposeEnum::Registration
                ]
            );

            $data = $this->gateway->initialize($dto);

            return ResponseBuilder::asSuccess()
                ->withMessage('Payment initialized')
                ->withData($data)
                ->build();
        } catch (HttpException $e) {
            return ResponseBuilder::asError($e->getStatusCode())
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function confirm(VerifyPaymentRequest $request)
    {
        try {
            $data = $this->gateway->verify($request->validated('reference'));

            return ResponseBuilder::asSuccess()
                ->withMessage('Payment verified successfully')
                ->withData($data)
                ->build();
        } catch (HttpException $e) {
            return ResponseBuilder::asError($e->getStatusCode())
                ->withMessage($e->getMessage())
                ->build();
        }
    }

    public function paystackWebhook(Request $request)
    {
        $payload = $request->getContent();

        if (! PaystackService::verifyWebhook($request, $payload)) {
            return response('Invalid signature', 403);
        }

        $this->webhook($payload, 'paystack');
    }

    protected function webhook($payload, $gateway)
    {
        WebhookEvent::create([
            'payment_gateway' => $gateway,
            'log' => $payload,
        ]);

        return response('Webhook received and processed.', 200);
    }
}
