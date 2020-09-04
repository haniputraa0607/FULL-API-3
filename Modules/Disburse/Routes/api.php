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
Route::group(['prefix' => 'disburse'], function () {

    Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:be']], function () {
        Route::any('dashboard', 'ApiDisburseController@dashboard');
        Route::any('outlets', 'ApiDisburseController@getOutlets');
        Route::any('user-franchise', 'ApiDisburseController@userFranchise');

        //setting bank name
        Route::any('setting/bank-name', 'ApiDisburseSettingController@bankNameList');
        Route::any('setting/bank-name/create', 'ApiDisburseSettingController@bankNameCreate');
        Route::any('setting/bank-name/edit/{id}', 'ApiDisburseSettingController@bankNameEdit');

        //settings bank
        Route::any('setting/bank-account', 'ApiDisburseSettingController@addBankAccount');
        Route::any('setting/edit-bank-account', 'ApiDisburseSettingController@editBankAccount');
        Route::any('setting/import-bank-account-outlet', 'ApiDisburseSettingController@importBankAccount');
        Route::any('bank', 'ApiDisburseSettingController@getBank');
        Route::any('setting/list-bank-account', 'ApiDisburseSettingController@listBankAccount');

        //settings mdr
        Route::get('setting/mdr', 'ApiDisburseSettingController@getMdr');
        Route::post('setting/mdr', 'ApiDisburseSettingController@updateMdr');
        Route::post('setting/mdr-global', 'ApiDisburseSettingController@updateMdrGlobal');

        //settings global
        Route::any('setting/fee-global', 'ApiDisburseSettingController@globalSettingFee');
        Route::any('setting/point-charged-global', 'ApiDisburseSettingController@globalSettingPointCharged');

        //disburse
        Route::post('list/trx', 'ApiDisburseController@listTrx');
        Route::post('list/fail-action', 'ApiDisburseController@listDisburseFailAction');
        Route::post('list/{status}', 'ApiDisburseController@listDisburse');
        Route::post('list-datatable/calculation', 'ApiDisburseController@listCalculationDataTable');
        Route::post('list-datatable/{status}', 'ApiDisburseController@listDisburseDataTable');
        Route::post('detail/{id}', 'ApiDisburseController@detailDisburse');

        //setting fee special outlet
        Route::any('setting/fee-outlet-special/outlets', 'ApiDisburseSettingController@getOutlets');
        Route::post('setting/fee-outlet-special/update', 'ApiDisburseSettingController@settingFeeOutletSpecial');
        Route::post('setting/outlet-special', 'ApiDisburseSettingController@settingOutletSpecial');

        //sync list bank
        Route::any('sync-bank', 'ApiDisburseController@syncListBank');

        //approver
        Route::any('setting/approver', 'ApiDisburseSettingController@settingApproverPayouts');

        //time to sent disburse
        Route::any('setting/time-to-sent', 'ApiDisburseSettingController@settingTimeToSent');

        //fee disburse
        Route::any('setting/fee-disburse', 'ApiDisburseSettingController@settingFeeDisburse');

        Route::any('update-status', 'ApiDisburseController@updateStatusDisburse');
    });

    Route::group(['middleware' => ['auth:user-franchise', 'scopes:be']], function () {
        Route::any('user-franchise/detail', 'ApiDisburseController@userFranchise');
        Route::any('user-franchise/dashboard', 'ApiDisburseController@dashboard');
        Route::any('user-franchise/outlets', 'ApiDisburseController@getOutlets');
        Route::any('user-franchise/user-franchise', 'ApiDisburseController@userFranchise');
        Route::any('user-franchise/bank', 'ApiDisburseSettingController@getBank');
        Route::post('user-franchise/reset-password', 'ApiDisburseController@userFranchiseResetPassword');

        //disburse
        Route::post('user-franchise/list/trx', 'ApiDisburseController@listTrx');
        Route::post('user-franchise/list/{status}', 'ApiDisburseController@listDisburse');
        Route::post('user-franchise/list-datatable/calculation', 'ApiDisburseController@listCalculationDataTable');
        Route::post('user-franchise/list-datatable/{status}', 'ApiDisburseController@listDisburseDataTable');
        Route::post('user-franchise/detail/{id}', 'ApiDisburseController@detailDisburse');
    });
});

Route::group(['prefix' => 'disburse'], function () {
    Route::any('iris/notification', 'ApiIrisController@notification');
});