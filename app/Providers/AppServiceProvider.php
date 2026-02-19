<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Payments\PaymentServiceInterface::class,
            function ($app) {
                $gateway = config('services.default_gateway');
                return match ($gateway) {
                    'fedapay' => new \App\Services\Payments\FedaPayService(),
                    'cinetpay' => new \App\Services\Payments\GeniusPayService('cinetpay'),
                    'geniuspay' => new \App\Services\Payments\GeniusPayService(),
                    default => new \App\Services\Payments\GeniusPayService(),
                };
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('otp-send', function (Request $request) {
            $ip = (string) $request->ip();
            $phone = (string) $request->input('phone', '');

            return [
                Limit::perMinute(5)->by($ip),
                Limit::perMinute(3)->by($phone !== '' ? $phone : $ip),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $ip = (string) $request->ip();
            $phone = (string) $request->input('phone', '');

            return [
                Limit::perMinute(10)->by($ip),
                Limit::perMinute(6)->by($phone !== '' ? $phone : $ip),
            ];
        });
    }
}
