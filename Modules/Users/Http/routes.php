<?php

Route::group(['prefix' => 'api'], function(){
	Route::get('users/list/{var}', 'Modules\Users\Http\Controllers\ApiUser@listVar');
	Route::group(['middleware' => ['auth_client','log_request'], 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
	    Route::post('new', 'ApiUser@newUser');
		Route::post('phone/check', 'ApiUser@check');
	    Route::post('pin/check', 'ApiUser@checkPin');
	    Route::post('pin/create', 'ApiUser@createPin');
	    Route::post('pin/resend', 'ApiUser@resendPin');
	    Route::post('pin/forgot', 'ApiUser@forgotPin');
	    Route::post('pin/verify', 'ApiUser@verifyPin');
	    Route::post('pin/change', 'ApiUser@changePin');
	    Route::post('update/profile', 'ApiUser@updateProfile');
	    Route::post('update/pin', 'ApiUser@updatePin');
	    Route::post('update/status', 'ApiUser@updateStatus');
	    Route::post('update/feature', 'ApiUser@updateFeature');
	    Route::post('profile', 'ApiUser@profile');
	    Route::post('profile/update', 'ApiUser@profileUpdate');
	});
	
	Route::group(['middleware' => ['auth_client'], 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
	    Route::any('summary', 'ApiUser@summaryUsers');
	    Route::post('check', 'ApiUser@check');
	    Route::post('fitur', 'ApiUser@fitur');		
	});
	
	Route::group(['middleware' => 'auth:api', 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
		
	    Route::post('granted-feature', 'ApiUser@getFeatureControl');
	    Route::get('rank/list', 'ApiUser@listRank');
	    Route::post('create', 'ApiUser@createUserFromAdmin');
	    
	    Route::post('list', 'ApiUser@list');
	    Route::post('adminoutlet/detail', 'ApiUser@detailAdminOutlet');
	    Route::post('adminoutlet/list', 'ApiUser@listAdminOutlet');
	    Route::post('adminoutlet/create', 'ApiUser@createAdminOutlet');
	    Route::post('adminoutlet/delete', 'ApiUser@deleteAdminOutlet');
	    Route::post('activity', 'ApiUser@activity');
	    Route::post('detail', 'ApiUser@show');
	    Route::post('log', 'ApiUser@log');
	    Route::post('delete', 'ApiUser@delete');
		Route::post('update', 'ApiUser@updateProfileByAdmin');
		Route::post('update/photo', 'ApiUser@updateProfilePhotoByAdmin');
		Route::post('update/password', 'ApiUser@updateProfilePasswordByAdmin');
		Route::post('update/level', 'ApiUser@updateProfileLevelByAdmin');
		Route::post('update/outlet', 'ApiUser@updateDoctorOutletByAdmin');
		Route::post('update/permission', 'ApiUser@updateProfilePermissionByAdmin');
		Route::post('update/outlet', 'ApiUser@updateUserOutletByAdmin');
	    Route::post('phone/verified', 'ApiUser@phoneVerified');
	    Route::post('phone/unverified', 'ApiUser@phoneUnverified');
		Route::post('email/verified', 'ApiUser@emailVerified');
	    Route::post('email/unverified', 'ApiUser@emailUnverified');
	    Route::post('inbox', 'ApiUser@inboxUser');
		Route::post('outlet', 'ApiUser@outletUser');
		Route::any('notification', 'ApiUser@getUserNotification');
	    
	});
	Route::group(['middleware' => 'auth:api', 'prefix' => 'home', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
	    Route::post('/', 'ApiHome@home');
	    Route::post('refresh-point-balance', 'ApiHome@refreshPointBalance');
	});
	
	Route::group(['prefix' => 'home', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
	    Route::any('notloggedin', 'ApiHome@homeNotLoggedIn');
	});

	Route::group(['middleware' => 'auth:api', 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
		// get user profile
	    Route::get('get', 'ApiUser@getUserDetail');
	    
	    // skip completes user profile
		Route::get('complete-profile/later', 'ApiWebviewUser@completeProfileLater');
		// submit complete user profile
		// Route::post('users/complete-profile', 'ApiWebviewUser@completeProfile');
	});

	// submit complete user profile
	Route::post('users/complete-profile', 'Modules\Users\Http\Controllers\ApiWebviewUser@completeProfile');
	
});

Route::group(['prefix' => 'user-delete', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
	Route::get('/{phone}', 'ApiUser@deleteUser');
	Route::post('/{phone}', 'ApiUser@deleteUserAction');
});
Route::group(['prefix' => 'api/cron', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
	Route::get('/reset-trx-day', 'ApiUser@resetCountTransaction');
});
