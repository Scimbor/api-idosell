<?php

namespace Api\Idosell;

use Illuminate\Support\ServiceProvider;

class IdosellApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/idosell.php', 'idosell'
        );

        $this->app->singleton('idosell-api', function ($app) {
            return new IdosellApiService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/idosell.php' => config_path('idosell.php'),
        ], 'idosell-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TestApiIdosellPackage::class,
            ]);
        }
    }
}
