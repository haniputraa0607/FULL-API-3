<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/balance', 'namespace' => 'Modules\Balance\Http\Controllers'], function()
{
    Route::post('topup', 'BalanceController@requestTopUpBalance');
    Route::post('balance', 'BalanceController@requestCashBackBalance');
    Route::post('point', 'BalanceController@requestPoint');
});

Route::group(['middleware' => ['auth_client', 'log_activities'], 'prefix' => 'api/balance', 'namespace' => 'Modules\Balance\Http\Controllers'], function()
{
	Route::group(['prefix' => 'topup'], function()
	{
	    Route::get('/', 'TopupNominalController@list');
	    Route::post('add', 'TopupNominalController@add');
	    Route::post('update', 'TopupNominalController@update');
	    Route::post('delete', 'TopupNominalController@delete');
	});
});

Route::group(['prefix' => 'api/v1/pos/saldo', 'middleware' => 'log_activities', 'namespace' => 'Modules\Balance\Http\Controllers'], function()
{
    Route::post('list', 'NewTopupController@topupNominalList');
    Route::post('topup', 'NewTopupController@topupNominalDo');
    Route::post('topup/confirm', 'NewTopupController@topupConfirm');
    Route::post('update', 'NewTopupController@update');
    Route::post('delete', 'NewTopupController@delete');
    Route::post('use', 'UseSaldoController@use');
    Route::post('use/approved', 'UseSaldoController@approved');
    Route::post('use/void', 'UseSaldoController@useVoid');
});

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/v1/pos/saldo', 'namespace' => 'Modules\Balance\Http\Controllers'], function()
{
    Route::post('topup/generate', 'NewTopupController@generateCode');
});