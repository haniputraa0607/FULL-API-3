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

Route::group(['prefix' => 'merchant'], function () {
    Route::group(['middleware' => ['auth:api', 'user_agent', 'log_activities', 'scopes:apps']], function () {
        Route::get('summary', 'ApiMerchantController@summaryOrder');
        Route::post('statistics', 'ApiMerchantController@statisticsOrder');
        Route::get('share-message', 'ApiMerchantController@shareMessage');
        Route::get('help-page', 'ApiMerchantController@helpPage');

        Route::get('register/introduction', 'ApiMerchantController@registerIntroduction');
        Route::get('register/success', 'ApiMerchantController@registerSuccess');
        Route::get('register/approved', 'ApiMerchantController@registerApproved');
        Route::get('register/rejected', 'ApiMerchantController@registerRejected');
        Route::post('register/submit/step-1', 'ApiMerchantController@registerSubmitStep1');
        Route::post('register/submit/step-2', 'ApiMerchantController@registerSubmitStep2');
        Route::get('register/detail', 'ApiMerchantController@registerDetail');

        Route::group(['prefix' => 'management'], function () {
            Route::post('product/variant/create-combination', 'ApiMerchantManagementController@variantCombination');
            Route::post('product/variant/delete', 'ApiMerchantManagementController@variantDelete');
            Route::any('product/list', 'ApiMerchantManagementController@productList');
            Route::post('product/create', 'ApiMerchantManagementController@productCreate');
            Route::post('product/detail', 'ApiMerchantManagementController@productDetail');
            Route::post('product/update', 'ApiMerchantManagementController@productUpdate');
            Route::post('product/delete', 'ApiMerchantManagementController@productDelete');
            Route::post('product/photo/delete', 'ApiMerchantManagementController@productPhotoDelete');

            //holiday
            Route::get('holiday/status', 'ApiMerchantController@holiday');
            Route::post('holiday/update', 'ApiMerchantController@holiday');

            //profile
            Route::get('profile/detail', 'ApiMerchantController@profileDetail');
            Route::post('profile/outlet/update', 'ApiMerchantController@profileOutletUpdate');
            Route::post('profile/pic/update', 'ApiMerchantController@profilePICUpdate');

            //address
            Route::get('address/detail', 'ApiMerchantController@addressDetail');
            Route::post('address/update', 'ApiMerchantController@addressDetail');

            //bank
            Route::get('bank/list', 'ApiMerchantController@bankList');
            Route::get('bank-account/list', 'ApiMerchantController@bankAccountList');
            Route::post('bank-account/check', 'ApiMerchantController@bankAccountCheck');
            Route::post('bank-account/create', 'ApiMerchantController@bankAccountCreate');
            Route::post('bank-account/delete', 'ApiMerchantController@bankAccountDelete');

            //delivery
            Route::get('delivery', 'ApiMerchantController@deliverySetting');
            Route::post('delivery/update-status', 'ApiMerchantController@deliverySettingUpdate');
        });

        Route::group(['prefix' => 'transaction'], function () {
            Route::post('/', 'ApiMerchantTransactionController@listTransaction');
            Route::post('detail', 'ApiMerchantTransactionController@detailTransaction');
            Route::get('status-count', 'ApiMerchantTransactionController@statusCount');

            //action
            Route::post('accept', 'ApiMerchantTransactionController@acceptTransaction');
            Route::post('reject', 'ApiMerchantTransactionController@rejectTransaction');
        });
    });

    Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:be']], function () {
        Route::get('register/introduction/detail', 'ApiMerchantController@registerIntroduction');
        Route::post('register/introduction/save', 'ApiMerchantController@registerIntroduction');
        Route::get('register/success/detail', 'ApiMerchantController@registerSuccess');
        Route::post('register/success/save', 'ApiMerchantController@registerSuccess');
        Route::get('register/approved/detail', 'ApiMerchantController@registerApproved');
        Route::post('register/approved/save', 'ApiMerchantController@registerApproved');
        Route::get('register/rejected/detail', 'ApiMerchantController@registerRejected');
        Route::post('register/rejected/save', 'ApiMerchantController@registerRejected');

        Route::post('list', 'ApiMerchantManagementController@list');
        Route::post('detail', 'ApiMerchantManagementController@detail');
        Route::post('update', 'ApiMerchantManagementController@update');
        Route::post('delete', 'ApiMerchantManagementController@delete');
        Route::any('candidate/list', 'ApiMerchantManagementController@canditateList');
        Route::post('candidate/update', 'ApiMerchantManagementController@canditateUpdate');
    });
});