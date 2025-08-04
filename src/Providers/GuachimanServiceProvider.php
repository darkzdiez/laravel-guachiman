<?php

namespace AporteWeb\Guachiman\Providers;

use Illuminate\Support\ServiceProvider;

class GuachimanServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/guachiman.php' => config_path('guachiman.php'),
            ], 'config');
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/guachiman.php', 'guachiman'
        );
    }
}
