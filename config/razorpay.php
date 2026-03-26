<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Razorpay Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all the settings needed for Razorpay
    | payment gateway integration.
    |
    */

    'key_id' => env('RAZORPAY_KEY_ID'),
    'key_secret' => env('RAZORPAY_KEY_SECRET'),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    
    // Test mode credentials
    'test' => [
        'key_id' => env('RAZORPAY_KEY_ID', 'rzp_test_s9Sp7DfRvL5w2d'),
        'key_secret' => env('RAZORPAY_KEY_SECRET', 'K8Zc2S8Nq5c8M2Nq7Vp1d'),
    ],
];
