<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
            $chat_id = $notifiable->notifiables->first()->tg_chat_id;
            $limit = $chat_id < 0 ? 20 : 60;

            return [
                Limit::perSecond(1),
                Limit::perMinute($limit)->by($chat_id),
            ];
        });

        if (App::environment(['staging', 'production'])) {
            URL::forceScheme('https');
        }
    }
}
