<?php

Route::group(['middleware' => ['web', 'auth:api'], 'prefix' => 'brand', 'namespace' => 'Modules\Brand\Http\Controllers'], function () {
    Route::get('/', 'ApiBrandController@index');

    Route::any('sync', 'ApiSyncBrandController@sync');
});
