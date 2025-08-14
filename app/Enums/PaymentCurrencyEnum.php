<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static NARIA()
 * @method static DOLLAR()
 * @method static POUND()
 * @method static
 */
final class PaymentCurrencyEnum extends Enum
{
    const NARIA = 'NGN';

    const DOLLAR = 'USD';

    const POUND = 'GBP';
}
