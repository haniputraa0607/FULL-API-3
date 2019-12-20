<?php
Route::group(['middleware' => ['auth:api-be','log_activities','user_agent'], 'prefix' => 'api/inboxglobal', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function()
{
    Route::post('list', ['middleware' => 'feature_control:114', 'uses' =>'ApiInboxGlobal@listInboxGlobal']);
    Route::post('detail', ['middleware' => 'feature_control:115', 'uses' =>'ApiInboxGlobal@detailInboxGlobal']);
    Route::post('create', ['middleware' => 'feature_control:116', 'uses' =>'ApiInboxGlobal@createInboxGlobal']);
    Route::post('update', ['middleware' => 'feature_control:117', 'uses' =>'ApiInboxGlobal@updateInboxGlobal']);
    Route::post('delete', ['middleware' => 'feature_control:118', 'uses' =>'ApiInboxGlobal@deleteInboxGlobal']);
});


Route::group(['middleware' => ['auth:api','log_activities','user_agent'], 'prefix' => 'api/inbox', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function()
{
    Route::any('user/{mode?}', 'ApiInbox@listInboxUser');
    Route::post('marked', 'ApiInbox@markedInbox');
    Route::post('unread', 'ApiInbox@unread');
});

Route::group(['middleware' => ['auth:api-be','log_activities','user_agent'], 'prefix' => 'api/inbox', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function() {
    Route::any('be/user/{mode?}', ['middleware' => 'feature_control:114', 'uses' =>'ApiInbox@listInboxUser']);
    Route::any('delete', ['middleware' => 'feature_control:118', 'uses' =>'ApiInbox@deleteInboxUser']);
});