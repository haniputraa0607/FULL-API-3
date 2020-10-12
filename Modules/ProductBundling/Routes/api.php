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

Route::middleware('auth:api')->get('/productbundling', function (Request $request) {
    return $request->user();
});

// Route::prefix('bundling')->group(function() {
//     Route::post('store', 'ApiBundlingController@store');
// });

// Route::group(['prefix' => 'bundling', 'middleware' => ['auth:api', 'log_activities'] ], function(){
// 	Route::post('store', 'ApiBundlingController@store');
// });

Route::group(['prefix' => 'product-bundling', 'middleware' => 'log_activities'], function()
{

    Route::group(['middleware' => 'auth:api'], function() {
        Route::post('store', 'ApiBundlingController@store');
        Route::get('list', 'ApiBundlingController@index');
        Route::post('brandproduct', 'ApiBundlingController@brandProduct');
        // Route::get('brandproduct', 'ApiBundlingController@brandProduct')->name('brand.product');
    });

});
