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

Route::middleware('auth:api')->get('/consultasion', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'consultasion/transaction'], function () {
    Route::post('/check', 'ApiTransactionConsultasionController@checkTransaction');
    Route::post('/new', 'ApiTransactionConsultasionController@newTransaction');
    Route::post('/get', 'ApiTransactionConsultasionController@getTransaction');
    Route::get('/reminder/list', 'ApiTransactionConsultasionController@getSoonConsultasionList');
    Route::post('/reminder/detail', 'ApiTransactionConsultasionController@getSoonConsultasionDetail');
});