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
Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:apps'], 'prefix' => 'promo-campaign'], function () {
    // Route::post('getTag', 'ApiPromoCampaign@getTag');
});

// ADMIN BACKEND
Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:be'], 'prefix' => 'promo-campaign'], function () {
    Route::get('/', 'ApiPromoCampaign@index');
    Route::any('filter', 'ApiPromoCampaign@index');
    Route::post('detail', 'ApiPromoCampaign@detail');
    Route::post('getTag', 'ApiPromoCampaign@getTag');
    Route::post('getData', 'ApiPromoCampaign@getData');
    Route::post('check', 'ApiPromoCampaign@check');
    Route::post('step1', 'ApiPromoCampaign@step1');
    Route::post('step2', 'ApiPromoCampaign@step2');
    Route::post('delete', 'ApiPromoCampaign@delete');
    Route::post('report', 'ApiPromoCampaign@report');
    Route::post('coupon', 'ApiPromoCampaign@coupon');

    Route::post('show-step1', 'ApiPromoCampaign@showStep1');
    Route::post('show-step2', 'ApiPromoCampaign@showStep2');
});


// APPS
Route::group(['middleware' => ['auth:api', 'scopes:apps'], 'prefix' => 'promo-campaign'], function () {
    Route::post('check-validation', 'ApiPromoCampaign@checkValid');
});

// DEVELOPMENT
Route::group(['middleware' => ['auth:api', 'scopes:apps'], 'prefix' => 'promo-campaign'], function () {
    Route::post('validate', 'ApiPromoCampaign@validateCode');
});