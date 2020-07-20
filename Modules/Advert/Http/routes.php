<?php

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'api/advert', 'namespace' => 'Modules\Advert\Http\Controllers'], function()
{
    Route::post('create', 'AdvertController@create');
    Route::post('delete', 'AdvertController@destroy');
});

Route::group(['middleware' => ['auth_client', 'log_activities', 'user_agent'], 'prefix' => 'api/advert', 'namespace' => 'Modules\Advert\Http\Controllers'], function()
{
    Route::any('/', 'AdvertController@index');
});
