<?php

Route::group(['prefix' => 'api/spinthewheel', 'middleware' => 'log_activities', 'namespace' => 'Modules\SpinTheWheel\Http\Controllers'], function()
{
    Route::group(['middleware' => ['auth_client', 'user_agent']], function() {
	    Route::post('/items', 'ApiSpinTheWheelController@getItems');
	    Route::post('/spin', 'ApiSpinTheWheelController@spin');
	});

    Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps']], function() {
	    Route::get('/setting', 'ApiSpinTheWheelController@getSetting');
	    Route::post('/setting', 'ApiSpinTheWheelController@setting');
	});
});
