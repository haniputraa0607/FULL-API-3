<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// MOKA POS
Route::group(['middleware' => ['auth_client', 'log_activities_pos'], 'prefix' => 'moka'], function () {
    Route::any('account', 'ApiMokaPOS@indexAccount');

    Route::group(['prefix' => 'sync',], function () {
        Route::get('business', 'ApiMokaPOS@syncBusiness');
        Route::get('outlet', 'ApiMokaPOS@syncOutlet');
        Route::get('product', 'ApiMokaPOS@syncProduct');
    });
});
