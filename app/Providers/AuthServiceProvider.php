<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Route;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
        
        Route::group(['middleware' => ['custom_auth', 'decrypt_pin:password,username']], function () {
            Passport::tokensCan([
                'be' => 'Manage admin panel scope',
                'apps' => 'Manage mobile scope',
            ]);
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });

        Passport::tokensExpireIn(now()->addDays(15000));
    }
}
