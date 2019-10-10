<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/promotion', 'namespace' => 'Modules\Promotion\Http\Controllers'], function()
{
    Route::post('create', 'ApiPromotion@CreatePromotion');
    Route::post('step1', 'ApiPromotion@ShowPromotionStep1');
    Route::post('step2', 'ApiPromotion@ShowPromotionStep2');
    Route::post('step3', 'ApiPromotion@ShowCampaignStep2');
    Route::post('queue', 'ApiPromotion@addPromotionQueue');
    Route::post('update', 'ApiPromotion@update');
    Route::post('delete', 'ApiPromotion@delete');
    Route::any('list', 'ApiPromotion@list');
    Route::post('recipient/list', 'ApiPromotion@recipientPromotion');
    Route::post('sent/list', 'ApiPromotion@promotionSentList');
    Route::post('voucher/list', 'ApiPromotion@promotionVoucherList');
    Route::post('voucher/trx', 'ApiPromotion@promotionVoucherTrx');
    Route::post('linkclicked/list', 'ApiPromotion@promotionLinkClickedList');

    Route::group(['prefix' => 'deals'], function()
    {
        Route::any('', 'ApiPromotionDeals@list');
        Route::post('save', 'ApiPromotionDeals@save');
    
    });
});

Route::group(['prefix' => 'api/promotion', 'middleware' => 'log_activities', 'namespace' => 'Modules\Promotion\Http\Controllers'], function()
{
    Route::get('display_logo/{hash}', 'ApiPromotion@displayLogo');
    Route::any('queue', 'ApiPromotion@addPromotionQueue');
    Route::any('send', 'ApiPromotion@sendPromotion');
});
