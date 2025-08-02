<?php

namespace App\Actions;

use App\Models\PaymentCard;
use App\Models\Transaction;

class SaveTransactionAction
{
    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the action.
     *
     * @param object $paymentTransaction
     * @return Transaction
     */
    public function execute($paymentTransaction)
    {
        $transaction = new Transaction();
        $transaction->user_id = ($paymentTransaction->user_id == '' ? null : $paymentTransaction->user_id);
        $transaction->transactionable_id = $paymentTransaction->transactionable_id;
        $transaction->transactionable_type = $paymentTransaction->transactionable_type;
        $transaction->reference = \Illuminate\Support\Str::uuid();
        $transaction->payment_status = $paymentTransaction->status;
        $transaction->payment_gateway = $paymentTransaction->paymentGateway;
        $transaction->payment_method = $paymentTransaction->paymentMethod;
        $transaction->payment_purpose = $paymentTransaction->paymentPurpose;
        $transaction->gateway_reference = $paymentTransaction->reference;
        $transaction->amount = $paymentTransaction->amount;
        $transaction->metadata = $paymentTransaction->metadata;
        $transaction->currency = $paymentTransaction->currency;
        $transaction->gateway_response = $paymentTransaction->gateway_response;
        $transaction->discount = $paymentTransaction->metadata->discount ?? null;
        $transaction->save();

        return $transaction;
    }

    /**
     * Execute the action.
     *
     * @param object $paymentCard
     * @return PaymentCard
     */
    public function saveCard($paymentCard)
    {
        $card = new PaymentCard();
        $card->authorization_code = $paymentCard->authorization_code;
        $card->bin = $paymentCard->bin;
        $card->last4 = $paymentCard->last4;
        $card->exp_month = $paymentCard->exp_month;
        $card->exp_year = $paymentCard->exp_year;
        $card->channel = $paymentCard->channel;
        $card->card_type = $paymentCard->card_type;
        $card->bank = $paymentCard->bank;
        $card->country_code = $paymentCard->country_code;
        $card->brand = $paymentCard->brand;
        $card->account_name = $paymentCard->account_name;
        $card->save();

        return $card;
    }

}
