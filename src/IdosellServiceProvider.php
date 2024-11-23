<?php

namespace Api\Idosell;

use Illuminate\Support\ServiceProvider;

class IdosellServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/idosell.php', 'idosell');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/idosell.php' => config_path('idosell.php'),
        ], 'config');
    }
}
