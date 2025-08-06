<?php

namespace App\Interfaces;

use App\DTOs\PaymentPayload;
use App\Models\User;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function initialize(PaymentPayload $payload, User $user): array;

    public function verify(string $reference): array;

    public static function verifyWebhook(Request $request, string $payload): bool;

}
