<?php

namespace App\Services;

use App\DTOs\PaymentPayload;
use App\Enums\PaymentCurrencyEnum;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaystackService implements PaymentGatewayInterface
{
    protected string $baseUrl;

    protected string $secret;

    public const SUCCESS = 'success';

    public function __construct()
    {
        $this->baseUrl = config('payment.paystack.baseurl');
        $this->secret = config('payment.paystack.secret');
    }

    public function initialize(PaymentPayload $payload, User $user): array
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

            activity()->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'email' => $user->email,
                    'amount' => $payload->amount,
                    'currency' => $payload->currency ?? PaymentCurrencyEnum::NARIA,
                    'url' => "{$this->baseUrl}/transaction/initialize",
                ])
                ->log($message);

            throw new HttpException($status, $message);
        } catch (\Throwable $e) {
            Log::critical('Unexpected payment init error', ['error' => $e]);
            activity()->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'email' => $user->email,
                    'amount' => $payload->amount,
                    'currency' => $payload->currency ?? PaymentCurrencyEnum::NARIA,
                    'url' => "{$this->baseUrl}/transaction/initialize",
                    'exception' => $e,
                ])
                ->log($e->getMessage());
            throw new HttpException(500, 'Something went wrong while initializing the payment.');
        }
    }

    public function verify(string $reference): array
    {
        try {
            $response = Http::withToken($this->secret)
                ->get("{$this->baseUrl}/transaction/verify/{$reference}");
            $data = $response->throw()->json('data');

            $metaData = $data['metadata'];
            $transaction = Transaction::updateOrCreate(
                ['gateway_reference' => $reference],
                [
                    'amount' => (float) ($data['amount'] / 100),
                    'payment_status' => $data['status'], // failed, abandoned, ongoing, pending, processing, queued, reversed
                    'payment_gateway' => 'paystack',
                    'transactionable_id' => $metaData['transactionable_id'],
                    'transactionable_type' => $metaData['transactionable_type'],
                    'payment_method' => $data['channel'],
                    'payment_purpose' => $metaData['purpose'],
                    'reference' => Str::uuid(),
                    'currency' => $data['currency'],
                    'metadata' => json_encode($metaData),
                    'user_id' => $metaData['user_id'],
                    'gateway_response' => $data['gateway_response'],
                ]
            );

            Activity::withProperties([
                'reference' => $reference,
                'status' => $data['status'],
                'amount' => $data['amount'],
                'user_id' => $metaData['user_id'],
                'channel' => $data['channel'],
                'gateway_response' => $data['gateway_response'],
            ])->performedOn($transaction)
                ->log('Paystack payment verified successfully');

            if ($data['status'] !== self::SUCCESS) {
                $message = "Payment not successful: {$data['gateway_response']}";
                $status = 400;
                throw new HttpException($status, $message);
            }

            return [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => $data['status'],
                'channel' => $data['channel'],
                'reference' => $data['reference'],
                'gateway_response' => $data['gateway_response'],
                'paid_at' => $data['paid_at'],
                'metadata' => $metaData,
            ];
        } catch (HttpException $e) {
            Activity::withProperties([
                'reference' => $reference,
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ])->log('Payment verification failed (HttpException)');

            throw new HttpException($e->getStatusCode(), $e->getMessage());
        } catch (RequestException $e) {
            Log::error('Paystack Verification Error', [
                'response' => $e->response?->json(),
                'exception' => $e,
            ]);
            $message = $e->response?->json('message') ?? 'Unable to verify payment.';
            $status = $e->response?->status() ?? 400;

            Activity::withProperties([
                'reference' => $reference,
                'error' => $e->response?->json(),
                'status_code' => $e->response?->status(),
            ])->log('Payment verification failed (RequestException)');

            throw new HttpException($status, $message);
        } catch (\Throwable $e) {
            Log::critical('Unexpected verify payment error', ['error' => $e]);
            Activity::withProperties([
                'reference' => $reference,
                'exception' => $e->getMessage(),
            ])->log('Unexpected error during payment verification');

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
}
