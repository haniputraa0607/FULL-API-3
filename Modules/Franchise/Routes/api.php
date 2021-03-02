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

Route::group(['prefix' => 'franchise'], function () {
    Route::group(['middleware' => ['auth:franchise', 'scopes:franchise-super-admin']], function () {
        Route::group(['prefix' => 'user'], function() {
            Route::any('/', 'ApiUserFranchiseController@index');
            Route::post('store', 'ApiUserFranchiseController@store');
            Route::post('detail', 'ApiUserFranchiseController@detail');
            Route::post('update', 'ApiUserFranchiseController@update');
            Route::post('delete', 'ApiUserFranchiseController@destroy');

            Route::post('autoresponse', 'ApiUserFranchiseController@autoresponse');
            Route::post('autoresponse/new-user/update', 'ApiUserFranchiseController@updateAutoresponse');
        });
        Route::get('outlets', 'ApiUserFranchiseController@allOutlet');
        Route::post('profile', 'ApiUserFranchiseController@updateProfile');
    });

    Route::group(['middleware' => ['auth:franchise', 'scopes:franchise']], function () {
        Route::group(['prefix' => 'user'], function() {
            Route::post('update-first-pin', 'ApiUserFranchiseController@updateFirstPin');
            Route::post('detail/for-login', 'ApiUserFranchiseController@detail');
        });
    });

    Route::group(['middleware' => ['auth:franchise', 'scopes:franchise-admin']], function () {
        Route::group(['prefix' => 'transaction'], function () {
		    Route::any('filter', 'ApiTransactionFranchiseController@transactionFilter');
		    Route::post('detail','ApiTransactionFranchiseController@transactionDetail');

		    Route::get('export','ApiTransactionFranchiseController@listExport');
	        Route::post('export','ApiTransactionFranchiseController@newExport');
	        Route::delete('export/{export_queue}','ApiTransactionFranchiseController@destroyExport');
	        Route::any('export/action', 'ApiTransactionFranchiseController@actionExport');
		});

		Route::group(['prefix' => 'product'], function() {
            Route::post('list','ApiTransactionFranchiseController@listProduct');
		    Route::post('category/list','ApiTransactionFranchiseController@listProductCategory');
        });

        Route::group(['prefix' => 'report-payment'], function() {
            Route::post('summary', 'ApiReportPaymentController@summaryPaymentMethod');
            Route::post('summary/detail', 'ApiReportPaymentController@summaryDetailPaymentMethod');
            Route::post('summary/chart', 'ApiReportPaymentController@summaryChart');
            Route::post('list', 'ApiReportPaymentController@listPayment');
            Route::get('payments', 'ApiReportPaymentController@payments');
        });

        Route::group(['prefix' => 'report-disburse'], function() {
            Route::post('summary', 'ApiReportDisburseController@summary');
            Route::post('list-transaction', 'ApiReportDisburseController@listTransaction');
        });
        Route::group(['prefix' => 'outlet'], function () {
		    Route::get('detail','ApiOutletFranchiseController@detail');
		    Route::post('update','ApiOutletFranchiseController@update');
		    Route::post('update-schedule','ApiOutletFranchiseController@updateSchedule');
		});
    });

});