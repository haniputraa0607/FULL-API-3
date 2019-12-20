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

Route::middleware('auth:api-be')->get('/user', function (Request $request) {
    return json_decode($request->user(), true);
});

Route::group(['middleware' => ['auth:api-be', 'log_activities'] ], function(){
	Route::get('granted-feature', 'Controller@getFeatureControl');
	Route::get('feature', 'Controller@getFeature');
	Route::get('feature-module', 'Controller@getFeatureModule');
	Route::get('rank/list', 'Controller@listRank');
	Route::get('config', 'Controller@getConfig');
	// Route::any('city/list', 'Controller@listCity');
	// Route::get('province/list', 'Controller@listProvince');
	Route::post('summernote/upload/image', 'Controller@uploadImageSummernote');
	Route::post('summernote/delete/image', 'Controller@deleteImageSummernote');
});

/* NO AUTH */
Route::any('city/list', 'Controller@listCity');
Route::get('province/list', 'Controller@listProvince');
Route::get('courier/list', 'Controller@listCourier');
Route::get('time', function() {
	date_default_timezone_set('Asia/Jakarta');
	$am = App\Http\Models\Setting::where('key', 'processing_time')->first();
	return response()->json(['time' => date('Y-m-d H:i:s'), 'processing' => $am['value'], 'new_format' => date('Y-m-d H:i:s+0000'), 'time_add' => date('Y-m-d H:i:s+0000', strtotime('+ '.$am['value'].' minutes'))]);
});
