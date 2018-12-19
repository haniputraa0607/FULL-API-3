<?php

Route::group(['prefix' => 'api/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::group(['middleware' => 'auth_client'], function() {
    	Route::post('create', 'ApiEnquiries@create');
	});
	
	/* AUTH */
	Route::group(['middleware' => 'auth:api'], function() {
    	Route::any('list', 'ApiEnquiries@index');
    	Route::any('detail', 'ApiEnquiries@indexDetail');
    	Route::post('reply', 'ApiEnquiries@reply');
    	Route::post('update', 'ApiEnquiries@update');
    	Route::post('delete', 'ApiEnquiries@delete');
	});
});
