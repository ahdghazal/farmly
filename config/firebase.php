<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project Credentials
    |--------------------------------------------------------------------------
    |
    | Here you may specify the credentials for your Firebase project. The 
    | values can be obtained from the Firebase Console under Project Settings
    | and Service Accounts.
    |
    */

    'credentials' => [
        'type' => env('FIREBASE_CREDENTIALS_TYPE', 'service_account'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
        'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'client_id' => env('FIREBASE_CLIENT_ID'),
        'auth_uri' => env('FIREBASE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth'),
        'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
        'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
        'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | Here you may specify the database URL for your Firebase project.
    |
    */

    'database_url' => env('FIREBASE_DATABASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | Here you may specify the storage bucket for your Firebase project.
    |
    */

    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),

    /*
    |--------------------------------------------------------------------------
    | Default Firebase Auth Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default Firebase auth guard to be used by your
    | application. This will be used for authenticating users.
    |
    */

    'auth_guard' => env('FIREBASE_AUTH_GUARD', 'firebase'),
];
