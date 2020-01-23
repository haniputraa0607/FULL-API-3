<?php

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'api/autocrm', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function()
{
    Route::get('listPushNotif', 'ApiAutoCrm@listPushNotif');
});

Route::group(['prefix' => 'api/autocrm/cron', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function()
{
    Route::get('run', 'ApiAutoCrmCron@cronAutocrmCron');
    Route::any('list', 'ApiAutoCrmCron@listAutoCrmCron');
    Route::post('create', 'ApiAutoCrmCron@createAutocrmCron');
    Route::post('update', 'ApiAutoCrmCron@updateAutocrmCron');
    Route::post('delete', 'ApiAutoCrmCron@deleteAutocrmCron');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/autocrm', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function()
{
    Route::get('list', ['middleware' => 'feature_control:199', 'uses' => 'ApiAutoCrm@listAutoCrm']);
    Route::post('update', ['middleware' => 'feature_control:122', 'uses' =>'ApiAutoCrm@updateAutoCrm']);
    Route::get('textreplace', 'ApiAutoCrm@listTextReplace');
    Route::post('textreplace/update', 'ApiAutoCrm@listTextReplace');
    Route::get('textreplace/{var}', 'ApiAutoCrm@listTextReplace');
});