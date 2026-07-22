<?php

return [
    'default' => env('CARRIER_DEFAULT', 'cdek'),

    'cdek' => [
        'base_url' => env('CDEK_BASE_URL', 'https://api.edu.cdek.ru/v2'),
        'client_id' => env('CDEK_CLIENT_ID'),
        'client_secret' => env('CDEK_CLIENT_SECRET'),
        'timeout' => (int) env('CDEK_HTTP_TIMEOUT', 15),
    ],
];
