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
Route::middleware(['auth:api', 'scopes:apps','log_activities'])->post('/ipay88/pay', 'IPay88Controller@requestView');
// response from Ipay88
Route::post('/ipay88/update_status', 'IPay88Controller@updateStatus');