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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return json_decode($request->user(), true);
});

Route::group(['middleware' => ['auth:api'] ], function(){
	Route::get('granted-feature', 'Controller@getFeatureControl');
	Route::get('feature', 'Controller@getFeature');
	Route::get('feature-module', 'Controller@getFeatureModule');
	Route::get('rank/list', 'Controller@listRank');
	Route::get('config', 'Controller@getConfig');
	// Route::any('city/list', 'Controller@listCity');
	// Route::get('province/list', 'Controller@listProvince');
});

/* NO AUTH */
Route::any('city/list', 'Controller@listCity');
Route::get('province/list', 'Controller@listProvince');
Route::get('courier/list', 'Controller@listCourier');
Route::get('time', function() {
	date_default_timezone_set('Asia/Jakarta');
	return response()->json(['time' => date('Y-m-d H:i:s')]);
});
