<?php

namespace Klangch\LaravelIRCaptcha;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Klangch\LaravelIRCaptcha\Console\Commands\ClearExpiredCaptcha;

class LaravelIRCaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ir-captcha.php',
            'ir-captcha'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearExpiredCaptcha::class,
            ]);
        }

        RateLimiter::for('ir-captcha', function (Request $request) {
            $config = config('ir-captcha');

            return Limit::perMinute($config['throttle_per_minute'])->by($request->ip());
        });

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'ir-captcha');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ir-captcha');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/ir-captcha.php' => config_path('ir-captcha.php'),
            __DIR__ . '/../lang' => $this->app->langPath('vendor/ir-captcha'),
            __DIR__ . '/../resources/views' => resource_path('views/vendor/ir-captcha'),
        ]);
    }
}
