<?php

return [

    'default' => env('MAIL_MAILER', 'mailgun'),

    'mailers' => [
        // Solo Mailgun
        'mailgun' => [
            'transport' => 'mailgun',
            'domain'    => env('MAILGUN_DOMAIN'),
            'secret'    => env('MAILGUN_SECRET'),
            'endpoint'  => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        ],
        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],
        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@mg.yamycorp.com'),
        'name'    => env('MAIL_FROM_NAME', 'YamyCorp System'),
    ],

];
