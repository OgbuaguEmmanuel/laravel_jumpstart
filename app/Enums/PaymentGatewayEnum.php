<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static PAYSTACK()
 * @method static STRIPE()
 * @method static PAYPAL()
 */
final class PaymentGatewayEnum extends Enum
{
    const PAYSTACK = 'paystack';

    const STRIPE = 'stripe';

    const PAYPAL = 'paypal';
}
