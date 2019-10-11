<?php

Route::group(['middleware' => ['auth:api','log_request'], 'prefix' => 'api/campaign', 'namespace' => 'Modules\Campaign\Http\Controllers'], function()
{
    Route::post('create', 'ApiCampaign@CreateCampaign');
    Route::post('step1', 'ApiCampaign@ShowCampaignStep1');
    Route::post('step3', 'ApiCampaign@ShowCampaignStep2');
    Route::post('recipient', 'ApiCampaign@showRecipient');
    Route::post('send', 'ApiCampaign@SendCampaign');
    Route::post('update', 'ApiCampaign@update');
    Route::post('list', 'ApiCampaign@campaignList');
    Route::post('email/outbox/list', 'ApiCampaign@campaignEmailOutboxList');
    Route::post('email/outbox/detail', 'ApiCampaign@campaignEmailOutboxDetail');
    Route::post('email/queue/list', 'ApiCampaign@campaignEmailQueueList');
    Route::post('email/queue/detail', 'ApiCampaign@campaignEmailQueueDetail');
	Route::post('sms/outbox/list', 'ApiCampaign@campaignSmsOutboxList');
	Route::post('sms/outbox/detail', 'ApiCampaign@campaignSmsOutboxDetail');
    Route::post('sms/queue/list', 'ApiCampaign@campaignSmsQueueList');
    Route::post('sms/queue/detail', 'ApiCampaign@campaignSmsQueueDetail');
	Route::post('push/outbox/list', 'ApiCampaign@campaignPushOutboxList');
	Route::post('push/outbox/detail', 'ApiCampaign@campaignPushOutboxDetail');
    Route::post('push/queue/list', 'ApiCampaign@campaignPushQueueList');
    Route::post('push/queue/detail', 'ApiCampaign@campaignPushQueueDetail');
    Route::post('whatsapp/outbox/list', 'ApiCampaign@campaignWhatsappOutboxList');
    Route::post('whatsapp/queue/list', 'ApiCampaign@campaignWhatsappQueueList');

});

Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/campaign', 'namespace' => 'Modules\Campaign\Http\Controllers'], function()
{
    Route::post('step2', 'ApiCampaign@ShowCampaignStep2');
});

Route::group(['prefix' => 'api/campaign/cron', 'namespace' => 'Modules\Campaign\Http\Controllers'], function()
{
    Route::any('queue', 'ApiCampaign@insertQueue');
    Route::any('send', 'ApiCampaign@sendCampaignCron');
});