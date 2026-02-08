<?php
return [
    'default' => env('DEFAULT_PAYMENT_GATEWAY', 'credit_card'),

    'gateways' => [
        'credit_card' => [
            'enabled' => env('STRIPE_ENABLED', true),
            'provider' => 'stripe',
            'secret_key' => env('STRIPE_SECRET_KEY',"secret"),
            'public_key' => env('STRIPE_PUBLIC_KEY',"public"),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET',"webhook_secret"),
            'mode' => env('STRIPE_MODE', 'test'),
        ],

        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', true),
            'client_id' => env('PAYPAL_CLIENT_ID',"client_id"),
            'secret' => env('PAYPAL_SECRET',"secret"),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],

        'bank_transfer' => [
            'enabled' => env('BANK_TRANSFER_ENABLED', true),
            'processing_time' => env('BANK_TRANSFER_PROCESSING_TIME', '3-5 business days'),
        ],

        'cash_on_delivery' => [
            'enabled' => env('COD_ENABLED', true),
            'max_amount' => env('COD_MAX_AMOUNT', 5000),
        ],
    ],
];
