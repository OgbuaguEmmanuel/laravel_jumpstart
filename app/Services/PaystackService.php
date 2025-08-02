<?php

namespace App\Services;

use App\DTOs\PaymentPayload;
use App\Enums\PaymentCurrencyEnum;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaystackService implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = config('payment.paystack.baseurl');
        $this->secret = config('payment.paystack.secret');
    }

    public function initialize(PaymentPayload $payload): array
    {
        try {
            $response = Http::withToken($this->secret)
                ->post("{$this->baseUrl}/transaction/initialize", [
                    'email' => $payload->email,
                    'amount' => $payload->amount,
                    'currency' => $payload->currency ?? PaymentCurrencyEnum::NARIA,
                    'callback_url' => $payload->callbackUrl ?? app()->environment('local') ? config('payment.paystack.callbackUrl') : null,
                    'metadata' => $payload->metadata,
                ]);

            $response->throw();

            return $response->json('data');

        } catch (RequestException $e) {
            Log::error('Paystack Initialization Error', [
                'response' => $e->response?->json(),
                'exception' => $e,
            ]);

            $message = $e->response?->json('message') ?? 'Unable to initialize payment.';
            $status = $e->response?->status() ?? 400;
            throw new HttpException($status, $message);
        } catch (\Throwable $e) {
            Log::critical('Unexpected payment init error', ['error' => $e]);
            throw new HttpException(500, 'Something went wrong while initializing the payment.');
        }
    }

    public function verify(string $reference): array
    {
        try {
            $response = Http::withToken($this->secret)
                ->get("{$this->baseUrl}/transaction/verify/{$reference}");

                // do db insertion on success if not yet inserted by webhook, if inserted, update status

            return $response->throw()->json('data');
        } catch (RequestException $e) {
            report($e);
            $message = $e->response?->json('message') ?? 'Unable to verify payment.';
            $status = $e->response?->status() ?? 400;
            throw new HttpException($status, $message);
        } catch (\Throwable $e) {
            Log::critical('Unexpected verify payment error', ['error' => $e]);
            throw new HttpException(500, 'Something went wrong while veriying payment.');
        }
    }

    public static function verifyWebhook(Request $request, string $payload): bool
    {
        $signature = $request->header('X-Paystack-Signature');
        $secretKey = config('payment.paystack.secret');

        $expectedSignature = hash_hmac('sha512', $payload, $secretKey);
        if ($signature !== $expectedSignature) {
            Log::warning('Invalid Paystack webhook signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
            return false;
        }

        return true;
    }

    protected function process()
    {
        logger('continue from here later');

         // Verify the transaction
        // $transaction = $this->getFactory()->transaction->verify([
        //     'reference' => $reference,
        // ]);

        // // Verify that the transaction was successfully registered in our Paystack account
        // if ($transaction->status === false) {
        //     exit();
        // }

        // // Verify that our metadata was built into the transaction.
        // if (!isset($transaction->data->metadata)) {
        //     exit();
        // }

    }
}
