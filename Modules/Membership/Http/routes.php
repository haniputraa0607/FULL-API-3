<?php
Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::any('/detail/webview', 'ApiMembershipWebview@webview');
});

Route::group(['middleware'=>'auth:api', 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::any('/web/view', 'ApiMembershipWebview@detailWebview');
});

Route::group(['middleware' => ['auth:api-be', 'log_activities'], 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::post('be/list', 'ApiMembership@listMembership');
    Route::post('create', 'ApiMembership@create');
    Route::post('update', 'ApiMembership@update');
    Route::post('delete', 'ApiMembership@delete');
    Route::get('update/transaction', 'ApiMembership@updateSubtotalTrxUser');
});
