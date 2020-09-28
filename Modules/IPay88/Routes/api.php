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
// form for customer
Route::middleware(['auth:api', 'scopes:apps','log_activities'])->any('/ipay88/pay', 'IPay88Controller@requestView');
// response from Ipay88
Route::group(['prefix'=>'ipay88','middleware'=>['log_notif_ipay88']],function(){
	Route::post('detail/{type}', 'IPay88Controller@notifUser');
	Route::post('notif/{type}', 'IPay88Controller@notifIpay');
});