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
    Route::any('list', 'ApiFavoriteController@list');
    Route::post('create', 'ApiFavoriteController@store');
    Route::post('delete', 'ApiFavoriteController@destroy');
});