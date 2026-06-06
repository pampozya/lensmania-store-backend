<?php

namespace App\Providers;

use App\Contracts\PayPalWebhookVerifier;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\PayPalWebhookVerifier as PayPalWebhookVerifierService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PayPalWebhookVerifier::class, PayPalWebhookVerifierService::class);
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        if ($this->app->environment('local')) {
            \Illuminate\Support\Facades\URL::forceScheme('http');
        }
    }
}
