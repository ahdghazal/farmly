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


    'credentials_file' => env('FIREBASE_CREDENTIALS', base_path('config/firebase_credentials.json')),
  

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
