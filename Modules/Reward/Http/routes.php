<?php

Route::group(['middleware' => 'auth:api', 'prefix' => 'api/reward', 'namespace' => 'Modules\Reward\Http\Controllers'], function()
{
    Route::any('/list', 'ApiReward@list');
    Route::post('/create', 'ApiReward@create');
    Route::post('/update', 'ApiReward@update');
    Route::post('/delete', 'ApiReward@delete');
    Route::get('/active', 'ApiReward@listActive');
    Route::get('/my-coupon', 'ApiReward@myCoupon');
    Route::post('/buy', 'ApiReward@buyCoupon');
    Route::post('/winner', 'ApiReward@setWinnerChoosen');
});
