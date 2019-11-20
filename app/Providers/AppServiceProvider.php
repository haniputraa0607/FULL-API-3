<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Laravel\Passport\Http\Controllers\AccessTokenController::class, \App\Http\Controllers\AccessTokenController::class);
		$this->app->bind('mailgun.client', function() {
			return \Http\Adapter\Guzzle6\Client::createWithConfig([
			
			]);
		});
        if ($this->app->environment() == 'local') {
            $this->app->register(\Reliese\Coders\CodersServiceProvider::class);
        }
    }
}
