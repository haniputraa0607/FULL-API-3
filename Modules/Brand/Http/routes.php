<?php

Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/brand', 'namespace' => 'Modules\Brand\Http\Controllers'], function () {
    Route::get('/', 'ApiBrandController@index');

    Route::get('list', 'ApiBrandController@listBrand');
    Route::post('sync', 'ApiSyncBrandController@syncBrand');
});
