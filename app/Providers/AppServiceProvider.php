<?php

namespace App\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Services\Payment\MockPaymentProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentProviderInterface::class, MockPaymentProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
