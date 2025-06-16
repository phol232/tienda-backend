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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'               => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Configuración para OAuth de Google
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'microsoft' => [
        'client_id'     => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect'      => env('MICROSOFT_REDIRECT_URI'),
        'tenant'        => env('MICROSOFT_TENANT', 'common'),
        'guzzle'        => [ 'verify' => false ],
    ],

    'apisunat' => [
        'persona_id' => env('APISUNAT_PERSONA_ID'),
        'token'      => env('APISUNAT_PERSONA_TOKEN'),
        'xml_url'    => env('APISUNAT_XML_URL'),
        'ruc'        => env('APISUNAT_RUC'),
    ],
    'apisperu' => [
        'base_url'         => env('APISPERU_BASE_URL'),
        'email'            => env('APISPERU_EMAIL'),
        'password'         => env('APISPERU_PASSWORD'),
        'company_ruc'      => env('APISPERU_RUC'),
        'company_name'     => env('APISPERU_NAME'),
        'company_trade'    => env('APISPERU_TRADE'),
        'company_address'  => env('APISPERU_COMPANY_ADDRESS'),
        'company_province' => env('APISPERU_COMPANY_PROVINCE'),
        'company_department' => env('APISPERU_COMPANY_DEPARTMENT'),
        'company_district'   => env('APISPERU_COMPANY_DISTRICT'),
        'company_ubigeo'     => env('APISPERU_COMPANY_UBIGEO'),
    ],
];
