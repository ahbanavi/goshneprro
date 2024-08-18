<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->mixedCase());

        RateLimiter::for('telegram', function (object $notifiable) {
            return [
                Limit::perSecond(30),
                Limit::perMinute(300)->by($notifiable->notifiables->first()->tg_chat_id),
            ];
        });
    }
}
