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
Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:apps']], 'prefix' => 'product-bundling'], function()
{
    Route::any('detail', 'ApiBundlingController@detailForApps');
});

Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be']], 'prefix' => 'product-bundling'], function()
{
    Route::get('list', 'ApiBundlingController@index');
    Route::post('store', 'ApiBundlingController@store');
    Route::post('be/detail', 'ApiBundlingController@detail');
    Route::post('update', 'ApiBundlingController@update');
    Route::any('outlet-available', 'ApiBundlingController@outletAvailable');
});
