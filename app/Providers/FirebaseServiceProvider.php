<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Firebase::class, function ($app) {
            $credentials = [
                'type' => config('firebase.credentials.type'),
                'project_id' => config('firebase.credentials.project_id'),
                'private_key_id' => config('firebase.credentials.private_key_id'),
                'private_key' => config('firebase.credentials.private_key'),
                'client_email' => config('firebase.credentials.client_email'),
                'client_id' => config('firebase.credentials.client_id'),
                'auth_uri' => config('firebase.credentials.auth_uri'),
                'token_uri' => config('firebase.credentials.token_uri'),
                'auth_provider_x509_cert_url' => config('firebase.credentials.auth_provider_x509_cert_url'),
                'client_x509_cert_url' => config('firebase.credentials.client_x509_cert_url'),
            ];

            return (new Factory)
                ->withServiceAccount($credentials)
                ->create();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
