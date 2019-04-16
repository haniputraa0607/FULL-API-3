<?php
Route::group(['middleware' => ['auth:api','log_request'], 'prefix' => 'api/inboxglobal', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function()
{
    Route::post('list', 'ApiInboxGlobal@listInboxGlobal');
    Route::post('detail', 'ApiInboxGlobal@detailInboxGlobal');
    Route::post('create', 'ApiInboxGlobal@createInboxGlobal');
    Route::post('update', 'ApiInboxGlobal@updateInboxGlobal');
    Route::post('delete', 'ApiInboxGlobal@deleteInboxGlobal');
});


Route::group(['middleware' => ['auth:api','log_request'], 'prefix' => 'api/inbox', 'namespace' => 'Modules\InboxGlobal\Http\Controllers'], function()
{
    Route::get('user', 'ApiInbox@listInboxUser');
    Route::post('marked', 'ApiInbox@markedInbox');
});