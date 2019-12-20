<?php

Route::group(['middleware' => ['auth:api-be','user_agent'], 'prefix' => 'api/enquiries', 'middleware' => 'log_activities', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{

    Route::post('create', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@create']);
    Route::any('list', ['middleware' => 'feature_control:160', 'uses' =>'ApiEnquiries@index']);
    Route::any('detail', ['middleware' => 'feature_control:160', 'uses' =>'ApiEnquiries@indexDetail']);
    Route::post('reply', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@reply']);
    Route::post('update', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@update']);
    Route::post('delete', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@delete']);

});

Route::group(['middleware' => ['auth:api','user_agent'], 'prefix' => 'api/enquiries', 'middleware' => 'log_activities', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::any('listEnquiries', 'ApiEnquiries@listEnquirySubject');
    Route::any('listPosition', 'ApiEnquiries@listEnquiryPosition');
});
