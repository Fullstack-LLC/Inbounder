<?php

namespace Fullstack\Inbounder;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InbounderServiceProvider extends ServiceProvider
{
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/inbounder.php' => config_path('inbounder.php'),
            ], 'config');
        }

        Route::macro('inbounder', function ($url) {
            return Route::post($url, '\Fullstack\Inbounder\MailgunWebhooksController');
        });
    }

    /**
     * Register application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/inbounder.php', 'inbounder');
    }
}
