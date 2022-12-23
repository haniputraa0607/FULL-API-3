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
    Route::group(['prefix' => 'external/management', 'middleware' => ['auth_client','log_activities_pos', 'scopes:merchant']], function () {
        Route::get('product/category/list', 'ApiMerchantExternalManagementController@listProductCategory');
        Route::post('product/variant/delete', 'ApiMerchantExternalManagementController@variantDelete');
        Route::any('product/list', 'ApiMerchantExternalManagementController@productList');
        Route::post('product/create', 'ApiMerchantExternalManagementController@productCreate');
        Route::post('product/detail', 'ApiMerchantExternalManagementController@productDetail');
        Route::post('product/update', 'ApiMerchantExternalManagementController@productUpdate');
        Route::post('product/delete', 'ApiMerchantExternalManagementController@productDelete');
        Route::post('product/stock/detail', 'ApiMerchantExternalManagementController@productStockDetail');
        Route::post('product/stock/update', 'ApiMerchantExternalManagementController@productStockUpdate');
        Route::post('product/photo/delete', 'ApiMerchantExternalManagementController@productPhotoDelete');
    });

    Route::group(['middleware' => ['auth:api', 'user_agent', 'log_activities', 'scopes:apps']], function () {
        Route::get('inbox', 'ApiMerchantController@inboxList');
        Route::post('inbox/marked', 'ApiMerchantController@inboxMarked');
        Route::post('inbox/marked-all', 'ApiMerchantController@inboxMarkedAll');

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

        Route::post('balance/detail', 'ApiMerchantController@balanceDetail');
        Route::post('balance/withdrawal', 'ApiMerchantController@balanceWithdrawal');
        Route::post('balance/withdrawal/fee', 'ApiMerchantController@balanceWithdrawalFee');

        Route::group(['prefix' => 'management'], function () {
            Route::post('product/variant/create-combination', 'ApiMerchantManagementController@variantCombination');
            Route::post('product/variant/update', 'ApiMerchantManagementController@variantGroupUpdate');
            Route::post('product/variant/delete', 'ApiMerchantManagementController@merchantVariantDelete');
            Route::any('product/list', 'ApiMerchantManagementController@merchantProductList');
            Route::post('product/create', 'ApiMerchantManagementController@merchantProductCreate');
            Route::post('product/detail', 'ApiMerchantManagementController@merchantProductDetail');
            Route::post('product/update', 'ApiMerchantManagementController@merchantProductUpdate');
            Route::post('product/delete', 'ApiMerchantManagementController@merchantProductDelete');
            Route::post('product/stock/detail', 'ApiMerchantManagementController@merchantProductStockDetail');
            Route::post('product/stock/update', 'ApiMerchantManagementController@merchantProductStockUpdate');
            Route::post('product/photo/delete', 'ApiMerchantManagementController@merchantProductPhotoDelete');

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

        Route::group(['prefix' => 'grading'], function () {
            Route::any('/', 'ApiMerchantController@detailGrading');
            Route::post('/delete', 'ApiMerchantController@deleteDetailGrading');
            Route::post('/update', 'ApiMerchantController@updateDetailGrading');
        });


        Route::group(['prefix' => 'transaction'], function () {
            Route::post('/', 'ApiMerchantTransactionController@listTransaction');
            Route::post('detail', 'ApiMerchantTransactionController@detailTransaction');
            Route::post('detail/commission', 'ApiMerchantTransactionController@detailTransactionCommission');
            Route::get('status-count', 'ApiMerchantTransactionController@statusCount');

            //action
            Route::post('accept', 'ApiMerchantTransactionController@acceptTransaction');
            Route::post('reject', 'ApiMerchantTransactionController@rejectTransaction');
            Route::post('delivery/request', 'ApiMerchantTransactionController@requestDeliveryTransaction');
            Route::post('delivery/time-pickup', 'ApiMerchantTransactionController@listTimePickupDelivery');
            Route::post('delivery/confirm', 'ApiMerchantTransactionController@confirmDeliveryTransaction');
            Route::post('delivery/update-status', 'ApiMerchantTransactionController@dummyUpdateStatusDelivery');
            Route::post('delivery/tracking', 'ApiMerchantTransactionController@deliveryTracking');
            Route::get('total-pending', 'ApiMerchantTransactionController@getTotalTransactionPending');
        });

        //User Reseller Agent
        Route::group(['prefix' => 'user-reseller'], function () {
            Route::post('/register', 'ApiUserResellerMerchantController@register');
        });
        Route::group(['prefix' => 'search'], function () {
            Route::post('product/list', 'ApiMerchantCustomerController@product');
            Route::post('/list', 'ApiMerchantCustomerController@list');
            Route::get('/city', 'ApiMerchantCustomerController@city');
            Route::get('/order_by', 'ApiMerchantCustomerController@order_by');
            Route::get('/promo', 'ApiMerchantCustomerController@promo');
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
        Route::post('list-setting', 'ApiMerchantController@listSettingOption');

        Route::post('be/product/variant/create-combination', 'ApiMerchantManagementController@variantCombination');
        Route::post('be/product/create', 'ApiMerchantManagementController@merchantProductCreate');
        Route::post('be/product/detail', 'ApiMerchantManagementController@adminProductDetail');
        Route::post('be/product/variant/update', 'ApiMerchantManagementController@variantGroupUpdate');
        Route::post('list', 'ApiMerchantManagementController@list');
        Route::post('store', 'ApiMerchantManagementController@store');
        Route::post('detail', 'ApiMerchantManagementController@detail');
        Route::post('update', 'ApiMerchantManagementController@update');
        Route::post('update-grading', 'ApiMerchantManagementController@updateGrading');
        Route::post('delete', 'ApiMerchantManagementController@delete');
        Route::any('candidate/list', 'ApiMerchantManagementController@canditateList');
        Route::post('candidate/update', 'ApiMerchantManagementController@canditateUpdate');
        Route::get('user/list-not-register', 'ApiMerchantManagementController@userListNotRegister');

        //withdrawal
        Route::any('withdrawal/list', 'ApiMerchantManagementController@withdrawalList');
        Route::post('withdrawal/completed', 'ApiMerchantManagementController@withdrawalChangeStatus');

        Route::post('balance/list', 'ApiMerchantController@balanceList');

        //Reseller
        Route::group(['prefix' => 'be/reseller/'], function () {
            Route::post('candidate', 'ApiBeUserResellerMerchantController@candidate');
            Route::post('candidate/detail', 'ApiBeUserResellerMerchantController@candidateDetail');
            Route::post('candidate/update', 'ApiBeUserResellerMerchantController@candidateUpdate');
            Route::post('', 'ApiBeUserResellerMerchantController@index');
            Route::post('detail', 'ApiBeUserResellerMerchantController@detail');
            Route::post('update', 'ApiBeUserResellerMerchantController@update');
        });
    });
});
