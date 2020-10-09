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

Route::group(['middleware' => ['auth:api', 'scopes:be'], 'prefix' => 'be/product-variant'], function () {
    Route::any('/', 'ProductVariantController@index')->middleware('feature_control:32');
    Route::post('store', 'ProductVariantController@store')->middleware('feature_control:33');
    Route::post('edit', 'ProductVariantController@edit')->middleware('feature_control:34');
    Route::post('update', 'ProductVariantController@update')->middleware('feature_control:34');
    Route::any('position', 'ProductVariantController@position')->middleware('feature_control:34');
});
