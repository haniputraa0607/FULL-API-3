<?php

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'api/autocrm', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function () {
    Route::get('listPushNotif', 'ApiAutoCrm@listPushNotif');
});

Route::group(['prefix' => 'api/autocrm/cron', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function () {
    Route::get('run', 'ApiAutoCrmCron@cronAutocrmCron');
    Route::any('list', 'ApiAutoCrmCron@listAutoCrmCron');
    Route::post('create', 'ApiAutoCrmCron@createAutocrmCron');
    Route::post('update', 'ApiAutoCrmCron@updateAutocrmCron');
    Route::post('delete', 'ApiAutoCrmCron@deleteAutocrmCron');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/autocrm', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function () {
    Route::any('list', ['uses' => 'ApiAutoCrm@listAutoCrm']);
    Route::post('update', ['uses' => 'ApiAutoCrm@updateAutoCrm']);
    Route::get('textreplace', 'ApiAutoCrm@listTextReplace');
    Route::post('textreplace/update', 'ApiAutoCrm@listTextReplace');
    Route::get('textreplace/{var}', 'ApiAutoCrm@listTextReplace');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/autoresponse-with-code', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function () {
    Route::post('list', ['middleware' => 'feature_control:306', 'uses' => 'ApiAutoresponseWithCode@list']);
    Route::post('store', ['middleware' => 'feature_control:307', 'uses' => 'ApiAutoresponseWithCode@store']);
    Route::post('detail', ['middleware' => 'feature_control:308', 'uses' => 'ApiAutoresponseWithCode@detail']);
    Route::post('update', ['middleware' => 'feature_control:308', 'uses' => 'ApiAutoresponseWithCode@update']);
    Route::post('delete-code', ['middleware' => 'feature_control:309', 'uses' => 'ApiAutoresponseWithCode@deleteCode']);
    Route::post('delete-autoresponsecode', ['middleware' => 'feature_control:309', 'uses' => 'ApiAutoresponseWithCode@deleteAutoresponsecode']);
});
