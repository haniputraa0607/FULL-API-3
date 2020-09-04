<?php

Route::group(['prefix' => 'api/bundling', 'middleware' => 'log_activities', 'namespace' => 'Modules\ProductBundling\Http\Controllers'], function()
{
    Route::group(['middleware' => 'auth:api'], function() {
	    Route::post('/create', 'ApiBundlingController@store');
	});
});
