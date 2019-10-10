<?php

Route::group(['prefix' => 'api/spinthewheel', 'middleware' => 'log_activities_apps', 'namespace' => 'Modules\SpinTheWheel\Http\Controllers'], function()
{
    Route::group(['middleware' => 'auth_client'], function() {
	    Route::post('/items', 'ApiSpinTheWheelController@getItems');
	    Route::post('/spin', 'ApiSpinTheWheelController@spin');
	});

    Route::group(['middleware' => 'auth:api'], function() {
	    Route::get('/setting', 'ApiSpinTheWheelController@getSetting');
	    Route::post('/setting', 'ApiSpinTheWheelController@setting');
	});
});
