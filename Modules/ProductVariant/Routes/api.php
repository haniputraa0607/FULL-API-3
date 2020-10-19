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

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'prefix' => 'product-variant'], function () {
    Route::any('/', ['middleware' => 'feature_control:32', 'uses' => 'ApiProductVariantController@index']);
    Route::post('store', ['middleware' => 'feature_control:279', 'uses' => 'ApiProductVariantController@store']);
    Route::post('edit', ['middleware' => 'feature_control:281', 'uses' => 'ApiProductVariantController@edit']);
    Route::post('update', ['middleware' => 'feature_control:281', 'uses' => 'ApiProductVariantController@update']);
    Route::post('delete', ['middleware' => 'feature_control:282', 'uses' => 'ApiProductVariantController@destroy']);
    Route::post('import', ['uses' => 'ApiProductVariantController@import']);
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'prefix' => 'product-variant-group'], function () {
    Route::any('/', ['uses' => 'ApiProductVariantGroupController@productVariantGroup']);
    Route::any('list-price', ['uses' => 'ApiProductVariantGroupController@listPrice']);
    Route::any('update-price', ['uses' => 'ApiProductVariantGroupController@updatePrice']);
    Route::post('list-detail', 'ApiProductVariantGroupController@listDetail');
    Route::post('update-detail', 'ApiProductVariantGroupController@updateDetail');
    Route::post('export', 'ApiProductVariantGroupController@export');
});

Route::group(['prefix' => 'product-variant-group'], function () {
    Route::any('rerer', ['uses' => 'ApiProductVariantController@getProductVariantRecursive']);
});
