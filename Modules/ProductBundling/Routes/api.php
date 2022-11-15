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
Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:apps']], 'prefix' => 'product-bundling'], function () {
    Route::any('detail', 'ApiBundlingController@detailForApps');
});

Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be']], 'prefix' => 'product-bundling'], function () {
    Route::any('list', 'ApiBundlingController@index');
    Route::post('store', 'ApiBundlingController@store');
    Route::post('be/detail', 'ApiBundlingController@detail');
    Route::post('update', 'ApiBundlingController@update');
    Route::any('outlet-available', 'ApiBundlingController@outletAvailable');
    Route::post('global-price', 'ApiBundlingController@globalPrice');
    Route::post('delete', 'ApiBundlingController@destroy');
    Route::post('delete-product', 'ApiBundlingController@destroyBundlingProduct');
    Route::post('position/assign', 'ApiBundlingController@positionBundling');
    Route::any('setting', 'ApiBundlingController@setting');

    Route::get('sync-date-today', 'ApiBundlingController@bundlingToday');
});

Route::group([[ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be']], 'prefix' => 'product-bundling-category'], function () {
    //bundling product
    Route::any('list', 'ApiBundlingCategoryController@listCategory');
    Route::post('store', 'ApiBundlingCategoryController@create');
    Route::post('detail', 'ApiBundlingCategoryController@detail');
    Route::post('update', 'ApiBundlingCategoryController@update');
    Route::post('delete', 'ApiBundlingCategoryController@delete');
    Route::post('position/assign', 'ApiBundlingCategoryController@positionCategory');
});
