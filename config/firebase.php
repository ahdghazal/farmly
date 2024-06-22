<?php

return [
    'default' => env('FIREBASE_PROJECT', 'app'),

    'projects' => [
        'app' => [
            'credentials' => [
                'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-service-account.json')),
                'auto_discovery' => true,
            ],
            'auth' => [
                'tenant_id' => null,
            ],
            'database' => [
                'url' => env('FIREBASE_DATABASE_URL'),
            ],
            'dynamic_links' => [
                'default_domain' => null,
            ],
            'storage' => [
                'default_bucket' => env('FIREBASE_STORAGE_BUCKET'),
            ],
            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),
            'logging' => [
                'http_log_channel' => null,
                'http_debug_log_channel' => null,
            ],
            'http_client_options' => [
                'proxy' => null,
                'timeout' => null,
            ],
        ],
    ],
];
