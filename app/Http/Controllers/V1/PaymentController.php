<?php

namespace App\Http\Controllers\V1;

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
use Spatie\Activitylog\Facades\Activity;

class PaymentController extends Controller
{
    public function __construct(public PaymentGatewayInterface $gateway) {}

    public function initialize(InitiatePaymentRequest $request)
    {
        $user = Auth::user();
        $email = $user->email;
        $userId = $user->id;
        $amount = $request->validatedAmount();
        $callbackUrl = $request->validated('callbackUrl');
        $currency = $request->validated('currency') ?? PaymentCurrencyEnum::NARIA;

        $dto = new PaymentPayload(
            $email, $amount, $currency, $callbackUrl,
            [
                'user_id' => $userId,
                'transactionable_id' => $userId,
                'transactionable_type' => get_class($user),
                'email' => $email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'purpose' => PaymentPurposeEnum::Registration
            ]
        );

        $data = $this->gateway->initialize($dto, $user);

        Activity::causedBy($user)
            ->withProperties([
                'amount' => $amount,
                'currency' => $currency,
                'callback_url' => $callbackUrl,
                'reference' => $data['reference'],
                'authorization_url' => $data['authorization_url'],
                'access_code' => $data['access_code']
            ])
            ->log('Initialized a payment');

        return ResponseBuilder::asSuccess()
            ->withMessage('Payment initialized')
            ->withData($data)
            ->build();

    }

    public function confirm(VerifyPaymentRequest $request)
    {
        $reference = $request->validated('reference');
        $data = $this->gateway->verify($reference);

        Activity::causedBy(Auth::user())
            ->withProperties([
                'reference' => $reference,
                'status' => $data['status'],
                'channel' => $data['channel']
            ])
            ->log('Verified a payment');

        return ResponseBuilder::asSuccess()
            ->withMessage('Payment verified successfully')
            ->withData($data)
            ->build();
    }

    public function paystackWebhook(Request $request)
    {
        $payload = $request->getContent();
        if (! PaystackService::verifyWebhook($request, $payload)) {
            Activity::withProperties([
                'ip' => $request->ip(),
                'payload' => $payload,
            ])->log('Rejected webhook due to invalid Paystack signature');

            return ResponseBuilder::asError(403)
                ->withMessage('Invalid signature')
                ->withHttpCode(403)
                ->build();
        }

        return $this->webhook($payload, 'paystack');
    }

    protected function webhook($payload, $gateway)
    {
        WebhookEvent::create([
            'payment_gateway' => $gateway,
            'log' => $payload,
        ]);

        Activity::withProperties([
            'gateway' => $gateway,
            'payload' => json_decode($payload, true),
            'ip' => request()->ip(),
        ])->log("Received webhook from {$gateway}");

        return ResponseBuilder::asSuccess()
            ->withHttpCode(200)
            ->withMessage('Webhook received and processed.')
            ->build();
    }
}
