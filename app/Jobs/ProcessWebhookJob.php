<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        logger('Processing webhook for gateway: ' . $this->paymentGateway);


        $this->verify($this->reference, $this->authorization);
    }

    protected function verify()
    {
        logger('verifying again');
         //  validate based on gateway to reconfirm webhook with actual api call

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

        $this->process();
    }

    protected function process()
    {
        logger('continue from here later');
    }
}


