<?php

use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'Lensmania Labs'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'frontend_url' => env('FRONTEND_URL', 'https://labs.lensmania.ae'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\Filament\AdminPanelProvider::class,
    ])->toArray(),
];
