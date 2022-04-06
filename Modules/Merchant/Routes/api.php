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

Route::group(['prefix' => 'merchant'], function () {
    Route::group(['middleware' => ['auth:api', 'user_agent', 'log_activities', 'scopes:apps']], function () {
        Route::get('register/introduction', 'ApiMerchantController@registerIntroduction');
        Route::get('register/success', 'ApiMerchantController@registerSuccess');
        Route::post('register/submit/step-1', 'ApiMerchantController@registerSubmitStep1');
        Route::post('register/submit/step-2', 'ApiMerchantController@registerSubmitStep2');
        Route::post('register/detail', 'ApiMerchantController@registerDetail');
    });

    Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:be']], function () {
        Route::get('register/introduction/detail', 'ApiMerchantController@registerIntroduction');
        Route::post('register/introduction/save', 'ApiMerchantController@registerIntroduction');
    });
});