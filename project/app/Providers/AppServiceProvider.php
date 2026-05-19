<?php

namespace App\Providers;

use App\Services\Messenger\MessengerProvider;
use App\Services\Messenger\Email;
use App\Services\Messenger\SMS;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        Email\GatewayInterface::class => Email\MockGateway::class,
        SMS\GatewayInterface::class, SMS\MockGateway::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(function (Application $app): MessengerProvider {
            return new MessengerProvider([
                'email' => $app->make(Email\GatewayInterface::class),
                'sms' => $app->make(SMS\GatewayInterface::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
