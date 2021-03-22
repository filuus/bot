<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Network;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('network', function() {
            return new Network();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
