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

Route::group(['middleware' => [], 'prefix' => 'transaction-note-format'], function() {
    Route::get('header', 'ApiTransactionNoteFormatController@getHeaderPlain');
    Route::get('header/{outletId}', 'ApiTransactionNoteFormatController@getHeader');
    Route::post('header', 'ApiTransactionNoteFormatController@setHeader');
    Route::get('footer', 'ApiTransactionNoteFormatController@getFooterPlain');
    Route::get('footer/{outletId}', 'ApiTransactionNoteFormatController@getFooter');
    Route::post('footer', 'ApiTransactionNoteFormatController@setFooter');
});
