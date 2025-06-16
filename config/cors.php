<?php
return [
    'paths' => [
        'api/*',
        'login',
        'sanctum/csrf-cookie',
    ],
    'allowed_methods'       => ['*'],
    'allowed_origins'       => [
        'http://localhost:5000',
        'https://tiendavir.netlify.app',
        'https://yamicorp.areallc.tech',
        'https://yamicorp2.areallc.tech'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'       => ['*'],
    'exposed_headers'       => [],
    'max_age'               => 0,
    'supports_credentials'  => true,
];
