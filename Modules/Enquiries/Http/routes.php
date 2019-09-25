<?php

Route::group(['prefix' => 'api/enquiries', 'middleware' => 'log_request', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::group(['middleware' => 'auth:api'], function() {
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

	Route::group(['middleware' => 'auth:api'], function() {
    	Route::any('listEnquiries', 'ApiEnquiries@listEnquirySubject');
	});
});
