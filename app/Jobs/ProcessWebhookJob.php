<?php

namespace App\Jobs;

use App\Actions\SaveTransactionAction;
use App\Enums\PaymentGatewayEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;

class ProcessWebhookJob implements ShouldQueue
{
    use Queueable;

    protected string $reference;

    protected mixed $authorization;

    protected string $paymentGateway;

    public function __construct(string $reference, mixed $authorization, string $gateway)
    {
        $this->reference = $reference;
        $this->authorization = $authorization;
        $this->paymentGateway = $gateway;
    }

    public function handle()
    {
        logger('Processing webhook for gateway: '.$this->paymentGateway);

        logger('Verifying again for gateway: '.$this->paymentGateway);

        if ($this->paymentGateway === PaymentGatewayEnum::PAYSTACK) {
            $paystackSecret = config('payment.paystack.secret');
            $paystackBaseUrl = config('payment.paystack.baseurl');

            try {
                $response = Http::withToken($paystackSecret)
                    ->get("{$paystackBaseUrl}/transaction/verify/{$this->reference}");

                $data = $response->throw()->json('data');

                if ($data['status'] !== 'success') {
                    Log::warning('Paystack transaction verification failed', [
                        'reference' => $this->reference,
                        'status' => $data['status'],
                        'gateway_response' => $data['gateway_response'],
                    ]);

                    return;
                }

                if (! isset($data['metadata'])) {
                    Log::error('Missing metadata in verified transaction', [
                        'reference' => $this->reference,
                    ]);

                    return;
                }

                $this->process($data);

            } catch (\Throwable $e) {
                Log::critical('Error verifying Paystack transaction in webhook job', [
                    'reference' => $this->reference,
                    'exception' => $e,
                ]);
            }
        }
    }

    protected function process(array $verifiedData)
    {
        logger('Continuing to process verified transaction');

        try {
            // Build the payment transaction type class
            $paymentTransactionType = $this->buildPaymentTransactionType($verifiedData);

            // Build the payment card class
            $paymentCard = $this->buildPaymentCard($verifiedData['authorization']);

            // Save the payment transaction
            $paymentTransaction = (new SaveTransactionAction)->execute($paymentTransactionType);

            // Save the payment card details
            $paymentCard = (new SaveTransactionAction)->saveCard($paymentCard);

            // Exit if the payment transaction is not successful
            if ($paymentTransaction->payment_status !== 'success') {
                // do something like send failure email
                exit();
            }

            // Proceed to main course of action
            switch ($paymentTransaction->payment_purpose) {
                // take actions and send success email
            }
        } catch (\InvalidArgumentException $e) {
            Log::error('An invalid argument exception occurred.', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            exit();
        } catch (\PDOException $e) {
            Log::error('A database query exception occurred.', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            exit();
        } catch (\Exception $e) {
            Log::error('An exception occurred.', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            exit();
        }
    }

    /**
     * Build the payment transaction type class.
     *
     * @param  mixed  $transaction
     * @return stdClass $paymentTransactionType
     */
    protected function buildPaymentTransactionType(array $transaction): stdClass
    {
        $paymentTransactionType = new stdClass;
        $paymentTransactionType->status = $transaction['status'];
        $paymentTransactionType->paymentGateway = $this->paymentGateway;
        $paymentTransactionType->paymentMethod = $transaction['channel'];
        $paymentTransactionType->paymentPurpose = $transaction['metadata']['purpose'] ?? null;
        $paymentTransactionType->reference = $transaction['reference'];
        $paymentTransactionType->amount = (float) ($transaction['amount'] / 100);
        $paymentTransactionType->currency = $transaction['currency'];
        $paymentTransactionType->gateway_response = $transaction['gateway_response'];
        $paymentTransactionType->metadata = json_encode($transaction['metadata']);
        $paymentTransactionType->user_id = $transaction['metadata']['user_id'] ?? null;
        $paymentTransactionType->transactionable_id = $transaction['metadata']['transactionable_id'] ?? null;
        $paymentTransactionType->transactionable_type = $transaction['metadata']['transactionable_type'] ?? null;
        $paymentTransactionType->discount = $transaction['metadata']['discount'] ?? null;

        return $paymentTransactionType;
    }

    /**
     * Build the payment card details
     *
     * @return stdClass $paymentCard
     */
    protected function buildPaymentCard(array $data)
    {
        $paymentCard = new stdClass;

        $paymentCard->authorization_code = $data['authorization_code'];
        $paymentCard->bin = $data['bin'];
        $paymentCard->last4 = $data['last4'];
        $paymentCard->exp_month = $data['exp_month'];
        $paymentCard->exp_year = $data['exp_year'];
        $paymentCard->channel = $data['channel'];
        $paymentCard->card_type = $data['card_type'];
        $paymentCard->bank = $data['bank'];
        $paymentCard->country_code = $data['country_code'];
        $paymentCard->brand = $data['brand'];
        $paymentCard->account_name = $data['account_name'];

        return $paymentCard;
    }
}
