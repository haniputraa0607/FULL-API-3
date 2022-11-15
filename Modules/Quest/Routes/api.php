<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'quest'], function () {
    Route::any('/', 'ApiQuest@index');
    Route::get('list-all', 'ApiQuest@listAllQuest');
    Route::any('list-deals', 'ApiQuest@listDeals');
    Route::any('list-quest-voucher', 'ApiQuest@listQuestVoucher');
    Route::any('list-product', 'ApiQuest@listProduct');
    Route::any('update-content', 'ApiQuest@updateContent');
    Route::any('update-quest', 'ApiQuest@updateQuest');
    Route::any('update-benefit', 'ApiQuest@updateBenefit');
    Route::any('category', 'ApiQuest@category');
    Route::any('create', 'ApiQuest@store');
    Route::any('create-detail', 'ApiQuest@storeQuestDetail');
    Route::any('detail', 'ApiQuest@show');
    Route::any('detail/update', 'ApiQuest@update');
    Route::any('destroy', 'ApiQuest@destroy');
    Route::post('start', 'ApiQuest@start');
    Route::post('trigger-manual-autoclaim', 'ApiQuest@triggerManualAutoclaim');

    Route::group(['prefix' => 'report'], function () {
        Route::any('/', 'ApiReportQuest@list');
        Route::any('detail', 'ApiReportQuest@detail');
        Route::any('list/user-quest', 'ApiReportQuest@listUser');
        Route::any('export', 'ApiReportQuest@exportListUser');
    });
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:apps'], 'prefix' => 'quest'], function () {
    Route::any('list', 'ApiQuest@list');
    Route::any('detail-apps', 'ApiQuest@detail');
    Route::any('take', 'ApiQuest@takeMission');
    Route::any('claim-benefit', 'ApiQuest@claimBenefit');
    Route::any('me', 'ApiQuest@me');
    Route::any('status', 'ApiQuest@status');
});
