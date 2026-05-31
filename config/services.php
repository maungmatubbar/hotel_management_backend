<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sslcommerz' => [
        'endpoint' => env('SSLCOMMERZ_ENDPOINT', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'currency' => env('SSLCOMMERZ_CURRENCY', 'BDT'),
        'success_url' => env('SSLCOMMERZ_SUCCESS_URL', 'http://localhost:3000/payment/{invoice_number}/success'),
        'fail_url' => env('SSLCOMMERZ_FAIL_URL', 'http://localhost:3000/payment/{invoice_number}/fail'),
        'cancel_url' => env('SSLCOMMERZ_CANCEL_URL', 'http://localhost:3000/payment/{invoice_number}/cancel'),
        'ipn_url' => env('SSLCOMMERZ_IPN_URL'),
        'customer_city' => env('SSLCOMMERZ_CUSTOMER_CITY', 'Dhaka'),
        'customer_country' => env('SSLCOMMERZ_CUSTOMER_COUNTRY', 'Bangladesh'),
    ],

];
