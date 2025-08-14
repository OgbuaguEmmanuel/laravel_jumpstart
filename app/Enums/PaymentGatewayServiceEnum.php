<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\PaypalService;
use App\Services\PaystackService;
use App\Services\StripeService;
use BenSampo\Enum\Enum;

/**
 * @method static Paystack()
 * @method static Stripe()
 * @method static Paypal()
 */
final class PaymentGatewayServiceEnum extends Enum
{
    const Paystack = PaystackService::class;

    const Stripe = StripeService::class;

    const Paypal = PaypalService::class;
}
