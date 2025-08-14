<?php

return [
    'driver' => env('PAYMENT_DRIVER', 'paystack'),
    'minimums' => [
        'NGN' => 50,
        'USD' => 1,
        'GBP' => 1,
        // Add more currencies and values as needed
    ],
    'symbols' => [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
    ],
    'paystack' => [
        'baseurl' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'callbackUrl' => env('PAYSTACK_CALLBACK_URL', config('app.url')),
    ],
];
