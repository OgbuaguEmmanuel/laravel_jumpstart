<?php

namespace App\DTOs;

class PaymentPayload
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $email,
        public int $amount, // in smallest currency unit e.g. kobo, cents
        public string $currency,
        public ?string $callbackUrl = null,
        public ?array $metadata = []
    ) {}
}
