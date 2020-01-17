<?php

Route::group(['middleware' => ['auth:api','user_agent','log_activities', 'scopes:ap'], 'prefix' => 'api/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{

    Route::any('list', ['middleware' => 'feature_control:160', 'uses' =>'ApiEnquiries@index']);
    Route::any('detail', ['middleware' => 'feature_control:160', 'uses' =>'ApiEnquiries@indexDetail']);
    Route::post('reply', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@reply']);
    Route::post('update', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@update']);
    Route::post('delete', ['middleware' => 'feature_control:161', 'uses' =>'ApiEnquiries@delete']);

});

Route::group(['middleware' => ['auth:api','user_agent','log_activities', 'scopes:*'], 'prefix' => 'api/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::post('create', 'ApiEnquiries@create');
    Route::any('listEnquiries', 'ApiEnquiries@listEnquirySubject');
    Route::any('listPosition', 'ApiEnquiries@listEnquiryPosition');
});
