<?php
Route::group(['middleware' => ['auth:api','log_activities'], 'prefix' => 'api/inboxglobal', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function()
{
    Route::post('list', 'ApiInboxGlobal@listInboxGlobal');
    Route::post('detail', 'ApiInboxGlobal@detailInboxGlobal');
    Route::post('create', 'ApiInboxGlobal@createInboxGlobal');
    Route::post('update', 'ApiInboxGlobal@updateInboxGlobal');
    Route::post('delete', 'ApiInboxGlobal@deleteInboxGlobal');
});


Route::group(['middleware' => ['auth:api','log_activities'], 'prefix' => 'api/inbox', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function()
{
    Route::any('user/{mode?}', 'ApiInbox@listInboxUser');
    Route::any('delete', 'ApiInbox@deleteInboxUser');
    Route::post('marked', 'ApiInbox@markedInbox');
    Route::post('unread', 'ApiInbox@unread');
});