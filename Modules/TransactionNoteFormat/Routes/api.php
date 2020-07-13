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

Route::group(['prefix' => 'transaction-note-format'], function() {
    Route::get('/{format_type}', 'ApiTransactionNoteFormatController@getPlain');
    Route::get('/{format_type}/{id_outlet}', 'ApiTransactionNoteFormatController@get');
    Route::post('/{format_type}', 'ApiTransactionNoteFormatController@set')
        ->middleware('auth:api', 'log_activities', 'user_agent', 'scopes:be');
});
