<?php

Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/brand', 'namespace' => 'Modules\Brand\Http\Controllers'], function () {
    Route::any('/', 'ApiBrandController@index');
    Route::any('list', 'ApiBrandController@listBrand');
    Route::post('store', 'ApiBrandController@store');
    Route::post('show', 'ApiBrandController@show');
    Route::post('delete', 'ApiBrandController@destroy');

    Route::post('sync', 'ApiSyncBrandController@syncBrand');
});
