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
        Route::get('register/introduction', 'ApiMerchantController@registerIntroduction');
        Route::get('register/success', 'ApiMerchantController@registerSuccess');
        Route::post('register/submit/step-1', 'ApiMerchantController@registerSubmitStep1');
        Route::post('register/submit/step-2', 'ApiMerchantController@registerSubmitStep2');
        Route::post('register/detail', 'ApiMerchantController@registerDetail');

        Route::group(['prefix' => 'management'], function () {
            Route::post('product/variant/create-combination', 'ApiMerchantManagementController@variantCombination');
            Route::post('product/variant/delete', 'ApiMerchantManagementController@variantDelete');
            Route::any('product/list', 'ApiMerchantManagementController@productList');
            Route::post('product/create', 'ApiMerchantManagementController@productCreate');
            Route::post('product/detail', 'ApiMerchantManagementController@productDetail');
            Route::post('product/update', 'ApiMerchantManagementController@productUpdate');
            Route::post('product/delete', 'ApiMerchantManagementController@productDelete');
            Route::post('product/photo/delete', 'ApiMerchantManagementController@productPhotoDelete');

            //profile
            Route::get('profile/detail', 'ApiMerchantManagementController@profileDetail');
            Route::post('profile/outlet/update', 'ApiMerchantManagementController@profileOutletUpdate');
            Route::post('profile/pic/update', 'ApiMerchantManagementController@profilePICUpdate');
        });
    });

    Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:be']], function () {
        Route::get('register/introduction/detail', 'ApiMerchantController@registerIntroduction');
        Route::post('register/introduction/save', 'ApiMerchantController@registerIntroduction');
        Route::get('register/success/detail', 'ApiMerchantController@registerSuccess');
        Route::post('register/success/save', 'ApiMerchantController@registerSuccess');

        Route::post('list', 'ApiMerchantManagementController@list');
        Route::post('detail', 'ApiMerchantManagementController@detail');
        Route::post('update', 'ApiMerchantManagementController@update');
        Route::post('delete', 'ApiMerchantManagementController@delete');
        Route::any('candidate/list', 'ApiMerchantManagementController@canditateList');
        Route::post('candidate/update', 'ApiMerchantManagementController@canditateUpdate');
    });
});