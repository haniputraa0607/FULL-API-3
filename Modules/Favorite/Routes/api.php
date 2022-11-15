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

Route::middleware(['auth:api', 'scopes:apps','log_activities'])->prefix('/favorite')->group(function () {
    Route::any('/', 'ApiFavoriteController@index');
    Route::any('list', 'ApiFavoriteController@listV2');
    Route::post('create', 'ApiFavoriteController@storeV2');
    Route::post('delete', 'ApiFavoriteController@destroy');
});
