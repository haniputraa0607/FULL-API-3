<?php

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/promotion', 'namespace' => 'Modules\Promotion\Http\Controllers'], function()
{
    Route::post('create', ['middleware' => 'feature_control:111', 'uses' => 'ApiPromotion@CreatePromotion']);
    Route::post('step1', ['middleware' => 'feature_control:111', 'uses' => 'ApiPromotion@ShowPromotionStep1']);
    Route::post('step2', ['middleware' => 'feature_control:111', 'uses' => 'ApiPromotion@ShowPromotionStep2']);
    Route::post('step3', ['middleware' => 'feature_control:111', 'uses' => 'ApiPromotion@ShowCampaignStep2']);
    Route::post('update', ['middleware' => 'feature_control:112', 'uses' => 'ApiPromotion@update']);
    Route::post('delete', ['middleware' => 'feature_control:113', 'uses' => 'ApiPromotion@delete']);
    Route::any('list', ['middleware' => 'feature_control:109', 'uses' => 'ApiPromotion@list']);
    Route::post('recipient/list', ['middleware' => 'feature_control:110', 'uses' => 'ApiPromotion@recipientPromotion']);
    Route::post('sent/list', ['middleware' => 'feature_control:110', 'uses' => 'ApiPromotion@promotionSentList']);
    Route::post('voucher/list', ['middleware' => 'feature_control:110', 'uses' => 'ApiPromotion@promotionVoucherList']);
    Route::post('voucher/trx', ['middleware' => 'feature_control:110', 'uses' => 'ApiPromotion@promotionVoucherTrx']);
    Route::post('linkclicked/list', ['middleware' => 'feature_control:110', 'uses' => 'ApiPromotion@promotionLinkClickedList']);

    Route::group(['prefix' => 'deals'], function()
    {
        Route::any('', ['middleware' => 'feature_control:109', 'uses' => 'ApiPromotionDeals@list']);
        Route::post('save', ['middleware' => 'feature_control:112', 'uses' => 'ApiPromotionDeals@save']);

    });
});

Route::group(['prefix' => 'api/promotion', 'middleware' => ['log_activities'], 'namespace' => 'Modules\Promotion\Http\Controllers'], function()
{
    Route::get('display_logo/{hash}', 'ApiPromotion@displayLogo');
});
