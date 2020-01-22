<?php

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:be'], 'prefix' => 'api/brand', 'namespace' => 'Modules\Brand\Http\Controllers'], function () {
    Route::any('/', ['middleware' => 'feature_control:155', 'uses' => 'ApiBrandController@index']);
    Route::any('be/list', ['middleware' => 'feature_control:155', 'uses' => 'ApiBrandController@listBrand']);
    Route::post('store', ['middleware' => 'feature_control:156', 'uses' => 'ApiBrandController@store']);
    Route::post('show', ['middleware' => 'feature_control:159', 'uses' => 'ApiBrandController@show']);
    Route::post('reorder', 'ApiBrandController@reOrder');
    Route::any('inactive-image', 'ApiBrandController@inactiveImage');

    Route::post('delete', ['middleware' => 'feature_control:158', 'uses' => 'ApiBrandController@destroy']);
    Route::group(['prefix' => 'delete'], function () {
        Route::post('outlet', ['middleware' => 'feature_control:158', 'uses' =>'ApiBrandController@destroyOutlet']);
        Route::post('product', ['middleware' => 'feature_control:158', 'uses' =>'ApiBrandController@destroyProduct']);
        Route::post('deals', ['middleware' => 'feature_control:158', 'uses' =>'ApiBrandController@destroyDeals']);
    });
    Route::post('outlet/list', 'ApiBrandController@outletList');
    Route::post('product/list', 'ApiBrandController@productList');

    Route::post('switch_status', 'ApiBrandController@switchStatus');
    Route::post('switch_visibility', 'ApiBrandController@switchVisibility');

    Route::post('outlet/store', 'ApiBrandController@outletStore');
    Route::post('product/store', 'ApiBrandController@productStore');

    Route::post('sync', 'ApiSyncBrandController@syncBrand');
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'api/brand', 'namespace' => 'Modules\Brand\Http\Controllers'], function () {
    Route::any('list', ['uses' => 'ApiBrandController@listBrand']);
});
