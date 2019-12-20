<?php

Route::group(['middleware' => 'auth:api-be', 'prefix' => 'api/enquiries', 'middleware' => 'log_activities', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{

    Route::post('create', 'ApiEnquiries@create');
    Route::any('list', 'ApiEnquiries@index');
    Route::any('detail', 'ApiEnquiries@indexDetail');
    Route::post('reply', 'ApiEnquiries@reply');
    Route::post('update', 'ApiEnquiries@update');
    Route::post('delete', 'ApiEnquiries@delete');

});

Route::group(['middleware' => 'auth:api', 'prefix' => 'api/enquiries', 'middleware' => 'log_activities', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::any('listEnquiries', 'ApiEnquiries@listEnquirySubject');
    Route::any('listPosition', 'ApiEnquiries@listEnquiryPosition');
});
