<?php

Route::group(['middleware' => ['auth:api','user_agent','log_activities', 'scopes:be'], 'prefix' => 'api/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function () {

    Route::any('list', ['middleware' => 'feature_control:83', 'uses' => 'ApiEnquiries@index']);
    Route::any('detail', ['middleware' => 'feature_control:84', 'uses' => 'ApiEnquiries@indexDetail']);
    Route::post('reply', ['middleware' => 'feature_control:84', 'uses' => 'ApiEnquiries@reply']);
    Route::post('update', ['middleware' => 'feature_control:84', 'uses' => 'ApiEnquiries@update']);
    Route::post('delete', ['middleware' => 'feature_control:84', 'uses' => 'ApiEnquiries@delete']);
});

Route::group(['middleware' => ['auth:api','user_agent','log_activities', 'scopes:apps'], 'prefix' => 'api/contact-us', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function () {
    Route::post('create', 'ApiEnquiries@createV2');
    Route::any('list-subject', 'ApiEnquiries@listEnquirySubject');
});

Route::group(['middleware' => ['auth:doctor-apps','user_agent','log_activities', 'scopes:doctor-apps'], 'prefix' => 'api/doctor/contact-us', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function () {
    Route::post('create', 'ApiEnquiries@createV2Doctor');
    Route::any('list-subject', 'ApiEnquiries@listEnquirySubjectDoctor');
});
