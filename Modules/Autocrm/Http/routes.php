<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/autocrm', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function()
{
    Route::get('list', 'ApiAutoCrm@listAutoCrm');
    Route::post('update', 'ApiAutoCrm@updateAutoCrm');
    Route::get('textreplace', 'ApiAutoCrm@listTextReplace');
    Route::post('textreplace/update', 'ApiAutoCrm@updateTextReplace');
    Route::get('textreplace/{var}', 'ApiAutoCrm@listTextReplace');
    
    Route::group(['prefix' => 'cron'], function()
    {
        Route::any('list', 'ApiAutoCrmCron@listAutoCrmCron');
        Route::post('create', 'ApiAutoCrmCron@createAutocrmCron');
        Route::post('update', 'ApiAutoCrmCron@updateAutocrmCron');
        Route::post('delete', 'ApiAutoCrmCron@deleteAutocrmCron');
    });
});

Route::group(['prefix' => 'api/autocrm/cron', 'namespace' => 'Modules\Autocrm\Http\Controllers'], function()
{
    Route::get('run', 'ApiAutoCrmCron@cronAutocrmCron');
});