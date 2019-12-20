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

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'user-feedback'], function () {
    Route::any('refuse', 'ApiUserFeedbackController@refuse');
    Route::post('create', 'ApiUserFeedbackController@store');
});

Route::group(['middleware' => ['auth:api-be', 'log_activities'], 'prefix' => 'user-feedback'], function () {
    Route::post('/', 'ApiUserFeedbackController@index');
    Route::post('detail', 'ApiUserFeedbackController@show');
    Route::post('get-detail', 'ApiUserFeedbackController@getDetail');
    Route::post('delete', 'ApiUserFeedbackController@destroy');
});
