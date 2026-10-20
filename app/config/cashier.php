<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cashier overrides
    |--------------------------------------------------------------------------
    |
    | Values here are merged on top of the vendor defaults, so anything that is
    | not listed (webhook path, currency, invoice settings, ...) keeps using
    | Laravel Cashier's own defaults.
    |
    */

    'key' => env('STRIPE_KEY', env('STRIPE_PUBLIC_KEY')),

    'secret' => env('STRIPE_SECRET', env('STRIPE_SECRET_KEY')),

    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

];
