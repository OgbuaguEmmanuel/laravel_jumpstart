<?php

namespace App\Interfaces;

use App\DTOs\PaymentPayload;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function initialize(PaymentPayload $payload): array;

    public function verify(string $reference): array;

    public static function verifyWebhook(Request $request, string $payload): bool;

}
