<?php

namespace App\Providers;

use App\Services\Messenger\MessengerResolver;
use App\Services\Messenger\Email;
use App\Services\Messenger\SMS;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Email\GatewayInterface::class, Email\MockGateway::class);
        $this->app->bind(SMS\GatewayInterface::class, SMS\MockGateway::class);

        $this->app->bind(function (Application $app): MessengerResolver {
            return new MessengerResolver([
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
