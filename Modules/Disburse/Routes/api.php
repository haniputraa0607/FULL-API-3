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
        Route::any('sycnFeeTransaction', 'ApiIrisController@sycnFeeTransaction');
        Route::post('sendRecap', 'ApiDisburseController@sendRecap');
        Route::post('sendRecapTransactionOultet', 'ApiDisburseController@sendRecapTransactionOultet');
        Route::post('sendRecapTransactionEachOultet', 'ApiDisburseController@sendRecapTransactionEachOultet');
        Route::post('sendRecapDisburseEachOultet', 'ApiDisburseController@sendRecapDisburseEachOultet');
        Route::any('dashboard', 'ApiDisburseController@dashboardV2');
        Route::any('outlets', 'ApiDisburseController@getOutlets');
        Route::any('user-franchise', 'ApiDisburseController@userFranchise');

        //setting bank name
        Route::any('setting/bank-name', 'ApiDisburseSettingController@bankNameList');
        Route::any('setting/bank-name/create', 'ApiDisburseSettingController@bankNameCreate');
        Route::any('setting/bank-name/edit/{id}', 'ApiDisburseSettingController@bankNameEdit');

        //settings bank
        Route::any('setting/bank-account', 'ApiDisburseSettingController@addBankAccount');
        Route::any('setting/edit-bank-account', 'ApiDisburseSettingController@editBankAccount');
        Route::any('setting/delete-bank-account', 'ApiDisburseSettingController@deleteBankAccount');
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

        //fee product plastic
        Route::any('setting/fee-product-plastic', 'ApiDisburseSettingController@settingFeeProductPlastic');

        //time to sent disburse
        Route::any('setting/time-to-sent', 'ApiDisburseSettingController@settingTimeToSent');

        //fee disburse
        Route::any('setting/fee-disburse', 'ApiDisburseSettingController@settingFeeDisburse');

        //send email to
        Route::any('setting/send-email-to', 'ApiDisburseSettingController@settingSendEmailTo');

        Route::any('update-status', 'ApiDisburseController@updateStatusDisburse');

        //rule promo payment gateway
        Route::group(['prefix' => 'rule-promo-payment-gateway'], function () {
            Route::any('/', 'ApiRulePromoPaymentGatewayController@index');
            Route::post('store', 'ApiRulePromoPaymentGatewayController@store');
            Route::post('detail', 'ApiRulePromoPaymentGatewayController@detail');
            Route::post('update', 'ApiRulePromoPaymentGatewayController@update');
            Route::post('delete', 'ApiRulePromoPaymentGatewayController@delete');
            Route::post('start', 'ApiRulePromoPaymentGatewayController@start');
            Route::post('mark-as-valid', 'ApiRulePromoPaymentGatewayController@markAsValid');
            Route::post('report', 'ApiRulePromoPaymentGatewayController@reportListTransaction');
            Route::post('summary', 'ApiRulePromoPaymentGatewayController@summaryListTransaction');
            Route::post('validation/export', 'ApiRulePromoPaymentGatewayController@validationExport');
            Route::post('validation/import', 'ApiRulePromoPaymentGatewayController@validationImport');
            Route::any('validation/report', 'ApiRulePromoPaymentGatewayController@validationReport');
            Route::post('validation/report/detail', 'ApiRulePromoPaymentGatewayController@validationReportDetail');
        });
    });

    Route::group(['middleware' => ['auth:user-franchise', 'scopes:be']], function () {
        Route::any('user-franchise/detail', 'ApiDisburseController@userFranchise');
        Route::any('user-franchise/dashboard', 'ApiDisburseController@dashboardV2');
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
