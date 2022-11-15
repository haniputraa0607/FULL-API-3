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

Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be']], 'prefix' => 'product-plastic'], function () {
    Route::any('list', 'ApiProductPlasticController@index');
    Route::post('store', 'ApiProductPlasticController@store');
    Route::post('detail', 'ApiProductPlasticController@detail');
    Route::post('update', 'ApiProductPlasticController@update');
    Route::post('delete', 'ApiProductPlasticController@destroy');
    Route::post('visibility', 'ApiProductPlasticController@visibility');

    Route::post('export-price', 'ApiProductPlasticController@exportProductPlaticPrice');
    Route::post('import-price', 'ApiProductPlasticController@importProductPlaticPrice');

    Route::post('export-product', 'ApiProductPlasticController@exportProduct');
    Route::post('import-product', 'ApiProductPlasticController@importProduct');

    Route::post('export-product-variant', 'ApiProductPlasticController@exportProductVariant');
    Route::post('import-product-variant', 'ApiProductPlasticController@importProductVariant');

    Route::post('export-plastic-status-outlet', 'ApiProductPlasticController@exportPlaticStatusOutlet');
    Route::post('import-plastic-status-outlet', 'ApiProductPlasticController@importPlaticStatusOutlet');

    Route::any('list-by-outlet', 'ApiProductPlasticController@listProductByOutlet');
    Route::post('update-stock', 'ApiProductPlasticController@updateStock');

    Route::post('list-use-plastic-product', 'ApiProductPlasticController@listUsePlasticProduct');
    Route::post('update-use-plastic-product', 'ApiProductPlasticController@updateUsePlasticProduct');
    Route::post('list-use-plastic-product-variant', 'ApiProductPlasticController@listUsePlasticProductVariant');
    Route::post('update-use-plastic-product-variant', 'ApiProductPlasticController@updateUsePlasticProductVariant');
});

Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be']], 'prefix' => 'plastic-type'], function () {
    Route::any('list', 'ApiPlasticTypeController@index');
    Route::post('store', 'ApiPlasticTypeController@store');
    Route::post('detail', 'ApiPlasticTypeController@detail');
    Route::post('update', 'ApiPlasticTypeController@update');
    Route::post('delete', 'ApiPlasticTypeController@destroy');
    Route::post('position', 'ApiPlasticTypeController@position');
});
