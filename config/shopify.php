<?php

return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'api_version' => env('SHOPIFY_API_VERSION', '2026-07'),
    'scopes' => array_filter(explode(',', env('SHOPIFY_SCOPES', 'read_orders'))),
    'app_url' => rtrim((string) env('APP_URL'), '/'),
    'offline_sessions_expire' => (bool) env('SHOPIFY_EXPIRING_OFFLINE_TOKENS', false),
];
