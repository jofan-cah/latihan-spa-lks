<?php

return [
    'paths'                => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'      => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    | Development  : localhost Vite ports + Herd .test domains
    | Production   : Ganti dengan domain production kamu
    |                contoh: 'https://pos.namakamu.com'
    */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:4173',
    ],

    'allowed_origins_patterns' => [
        '#^http://[a-z0-9\-]+\.test$#', // semua *.test (Herd/Valet dev)
    ],

    'allowed_headers'  => ['*'],
    'exposed_headers'  => [],
    'max_age'          => 0,
    'supports_credentials' => true,
];
