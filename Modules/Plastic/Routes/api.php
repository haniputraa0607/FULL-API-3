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

Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be']], 'prefix' => 'product-plastic'], function()
{
    Route::any('list', 'ApiProductPlasticController@index');
    Route::post('store', 'ApiProductPlasticController@store');
    Route::post('detail', 'ApiProductPlasticController@detail');
    Route::post('update', 'ApiProductPlasticController@update');
    Route::post('delete', 'ApiProductPlasticController@destroy');
    Route::post('visibility', 'ApiProductPlasticController@visibility');
});

// Route::post('/plastic/total-price', 'PlasticController@getTotalPrice')->middleware('auth:api');
