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
*///'scopes:disburse'============
Route::group(['middleware' => ['auth:api', 'user_agent'], 'prefix' => 'disburse'], function () {
    Route::any('dashboard', 'ApiDisburseController@dashboard');
    Route::any('outlets', 'ApiDisburseController@getOutlets');
    Route::any('user-franchise', 'ApiDisburseController@userFranchise');

    //setting bank name
    Route::any('setting/bank-name', 'ApiDisburseSettingController@bankNameList');
    Route::any('setting/bank-name/create', 'ApiDisburseSettingController@bankNameCreate');
    Route::any('setting/bank-name/edit/{id}', 'ApiDisburseSettingController@bankNameEdit');

    //settings bank
    Route::any('setting/bank-account', 'ApiDisburseSettingController@updateBankAccount');
    Route::any('bank', 'ApiDisburseSettingController@getBank');

    //settings mdr
    Route::get('setting/mdr', 'ApiDisburseSettingController@getMdr');
    Route::post('setting/mdr', 'ApiDisburseSettingController@updateMdr');
    Route::post('setting/mdr-global', 'ApiDisburseSettingController@updateMdrGlobal');

    //settings global
    Route::any('setting/fee-global', 'ApiDisburseSettingController@globalSettingFee');
    Route::any('setting/point-charged-global', 'ApiDisburseSettingController@globalSettingPointCharged');

    //disburse
    Route::post('list/trx', 'ApiDisburseController@listTrx');
    Route::post('list/{status}', 'ApiDisburseController@listDisburse');
    Route::post('list-datatable/{status}', 'ApiDisburseController@listDisburseDataTable');
    Route::post('detail/{id}', 'ApiDisburseController@detailDisburse');

    //sync list bank
    Route::any('sync-bank', 'ApiDisburseController@syncListBank');
});

Route::group(['prefix' => 'disburse'], function () {
    Route::any('iris/notification', 'ApiIrisController@notification');
});