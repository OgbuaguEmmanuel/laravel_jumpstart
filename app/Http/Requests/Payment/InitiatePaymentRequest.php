<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentCurrencyEnum;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    protected int $minAmount;

    protected string $currency;

    protected string $symbol;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Set up values to be reused.
     */
    protected function prepareForValidation()
    {
        $this->currency = $this->input('currency', PaymentCurrencyEnum::NARIA);
        $this->minAmount = config("payment.minimums.{$this->currency}", 1);
        $this->symbol = config("payment.symbols.{$this->currency}", strtoupper($this->currency));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'callbackUrl' => ['nullable', 'url'],
            'amount' => ['required', 'numeric', "min:{$this->minAmount}"],
            'currency' => ['nullable', new EnumValue(PaymentCurrencyEnum::class)],
        ];
    }

    /**
     * Hook to manipulate the error messages.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('amount')) {
                // Remove default message(s)
                $validator->errors()->forget('amount');

                $validator->errors()->add('amount', "The minimum amount is {$this->symbol}{$this->minAmount}.");
            }
        });
    }

    /**
     * Converts amount to smallest unit (e.g., kobo, cents).
     */
    public function validatedAmount(): int
    {
        return intval($this->input('amount') * 100);
    }
}
