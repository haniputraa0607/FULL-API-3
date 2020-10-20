<?php

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/campaign', 'namespace' => 'Modules\Campaign\Http\Controllers'], function()
{
    Route::post('create', ['middleware' => 'feature_control:100', 'uses' =>'ApiCampaign@CreateCampaign']);
    Route::post('step1', ['middleware' => 'feature_control:99', 'uses' =>'ApiCampaign@ShowCampaignStep1']);
    Route::post('step3', ['middleware' => 'feature_control:99', 'uses' =>'ApiCampaign@ShowCampaignStep2']);
    Route::post('recipient', ['middleware' => 'feature_control:99', 'uses' =>'ApiCampaign@showRecipient']);
    Route::post('send', ['middleware' => 'feature_control:101', 'uses' =>'ApiCampaign@SendCampaign']);
    Route::post('update', ['middleware' => 'feature_control:101', 'uses' =>'ApiCampaign@update']);
    Route::post('list', 'ApiCampaign@campaignList');
    Route::post('email/outbox/list', ['middleware' => 'feature_control:103', 'uses' =>'ApiCampaign@campaignEmailOutboxList']);
    Route::post('email/outbox/detail', ['middleware' => 'feature_control:103', 'uses' =>'ApiCampaign@campaignEmailOutboxDetail']);
	Route::post('sms/outbox/list', ['middleware' => 'feature_control:105', 'uses' =>'ApiCampaign@campaignSmsOutboxList']);
	Route::post('sms/outbox/detail', ['middleware' => 'feature_control:105', 'uses' =>'ApiCampaign@campaignSmsOutboxDetail']);
	Route::post('push/outbox/list', ['middleware' => 'feature_control:107', 'uses' =>'ApiCampaign@campaignPushOutboxList']);
	Route::post('push/outbox/detail', ['middleware' => 'feature_control:107', 'uses' =>'ApiCampaign@campaignPushOutboxDetail']);
    Route::post('whatsapp/outbox/list', 'ApiCampaign@campaignWhatsappOutboxList');
    Route::post('step2', ['middleware' => 'feature_control:99', 'uses' =>'ApiCampaign@ShowCampaignStep2']);
    Route::post('delete', ['middleware' => 'feature_control:102', 'uses' =>'ApiCampaign@destroy']);
});

Route::group(['prefix' => 'api/campaign', 'namespace' => 'Modules\Campaign\Http\Controllers'], function()
{
    Route::post('send2', ['uses' =>'ApiCampaign@SendCampaign']);
});