<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static Registration()
 * @method static Subscription()
 * @method static Renewal()
 */
final class PaymentPurposeEnum extends Enum
{
    const Subscription = 'subscription';

    const Registration = 'registration';

    const Renewal = 'Renewal';
}
