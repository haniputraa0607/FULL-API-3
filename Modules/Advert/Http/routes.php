<?php

Route::group(['middleware' => ['auth:api', 'log_request'], 'prefix' => 'api/advert', 'namespace' => 'Modules\Advert\Http\Controllers'], function()
{
    Route::post('create', 'AdvertController@create');
    Route::post('delete', 'AdvertController@destroy');
});

Route::group(['middleware' => ['auth_client', 'log_request'], 'prefix' => 'api/advert', 'namespace' => 'Modules\Advert\Http\Controllers'], function()
{
    Route::any('/', 'AdvertController@index');
});
