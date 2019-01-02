<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // custom validator deals_type
        // custom error message: resources/lang/en/validation.php (deals_type)
        Validator::extend('deals_type', function($attribute, $value, $parameters, $validator) {
            $deals_type = ['Deals', 'Hidden', 'Point', 'Spin', 'Subscription'];

            // the value can be string or array
            if (is_string($value)) {
                // if type in $value not exist in $deals_type
                if ( !in_array($value, $deals_type) ) {
                    return false;
                }
            }
            elseif (is_array($value)) {
                // if all types in $value not exist in $deals_type
                $intersect = array_intersect($value, $deals_type);
                if (count($intersect) != count($value)) {
                    return false;
                }
            }
            else {
                return false;
            }

            return true;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
