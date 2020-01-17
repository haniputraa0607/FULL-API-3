<?php
Route::group(['middleware' => ['auth:api', 'log_activities','user_agent', 'scopes:*'], 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::any('/detail/webview', 'ApiMembershipWebview@webview');
});

Route::group(['middleware'=>['auth:api', 'scopes:*'], 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::any('/web/view', 'ApiMembershipWebview@detailWebview');
});

Route::group(['middleware' => ['auth:api', 'log_activities','user_agent', 'scopes:ap'], 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::post('be/list', ['middleware' => 'feature_control:10', 'uses' =>'ApiMembership@listMembership']);
    Route::post('create', ['middleware' => 'feature_control:12', 'uses' =>'ApiMembership@create']);
    Route::post('update', ['middleware' => 'feature_control:13', 'uses' =>'ApiMembership@update']);
    Route::post('delete', ['middleware' => 'feature_control:14', 'uses' =>'ApiMembership@delete']);
    Route::get('update/transaction', ['middleware' => 'feature_control:13', 'uses' =>'ApiMembership@updateSubtotalTrxUser']);
});
