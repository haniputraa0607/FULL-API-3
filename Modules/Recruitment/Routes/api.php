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
Route::group(['middleware' => ['auth:api', 'scopes:be'], 'prefix' => 'hairstylist/be'], function () {
    Route::post('export-commision', 'ApiHairStylistController@exportCommision');
    Route::post('category/create', 'ApiHairStylistController@createCategory');
    Route::any('category', 'ApiHairStylistController@listCategory');
    Route::post('category/update', 'ApiHairStylistController@updateCategory');
    Route::post('category/delete', 'ApiHairStylistController@deleteCategory');
    Route::post('export-payroll', 'ApiIncome@export_income');
});

Route::group(['middleware' => ['log_activities', 'user_agent'], 'prefix' => 'recruitment'], function () {

    Route::group(['middleware' => ['auth_client', 'scopes:landing-page'], 'prefix' => 'hairstylist'], function () {
        Route::post('create', 'ApiHairStylistController@create');
    });

    Route::group(['middleware' => ['auth:api', 'scopes:be'], 'prefix' => 'hairstylist/be'], function () {
        Route::any('candidate/list', 'ApiHairStylistController@canditateList');
        Route::any('list', 'ApiHairStylistController@hsList');
        Route::post('detail', 'ApiHairStylistController@detail');
        Route::post('update', 'ApiHairStylistController@update');
        Route::post('update-status', 'ApiHairStylistController@updateStatus');
        Route::post('update-box', 'ApiHairStylistController@updateBox');
        Route::post('detail/document', 'ApiHairStylistController@detailDocument');
        Route::post('delete', 'ApiHairStylistController@delete');
        Route::post('info-order', 'ApiHairStylistController@totalOrder');
        Route::post('move-outlet', 'ApiHairStylistController@moveOutlet');
        Route::get('setting-requirements', 'ApiHairStylistController@candidateSettingRequirements');
        Route::post('setting-requirements', 'ApiHairStylistController@candidateSettingRequirements');

    	Route::group(['prefix' => 'schedule'], function () {
        	Route::post('list', 'ApiHairStylistScheduleController@list');
        	Route::post('detail', 'ApiHairStylistScheduleController@detail');
        	Route::post('update', 'ApiHairStylistScheduleController@update');
        	Route::get('outlet', 'ApiHairStylistScheduleController@outlet');
        	Route::get('year-list', 'ApiHairStylistScheduleController@getScheduleYear');
        	Route::post('create', 'ApiHairStylistScheduleController@create');
    	});

    	Route::group(['prefix' => 'timeoff'], function () {
        	Route::post('list', 'ApiHairStylistTimeOffOvertimeController@listTimeOff');
        	Route::post('delete', 'ApiHairStylistTimeOffOvertimeController@deleteTimeOff');
        	Route::post('detail', 'ApiHairStylistTimeOffOvertimeController@detailTimeOff');
        	Route::post('update', 'ApiHairStylistTimeOffOvertimeController@updateTimeOff');
        	Route::post('create', 'ApiHairStylistTimeOffOvertimeController@createTimeOff');
        	Route::post('list-hs', 'ApiHairStylistTimeOffOvertimeController@listHS');
        	Route::post('list-date', 'ApiHairStylistTimeOffOvertimeController@listDate');
    	});

    	Route::group(['prefix' => 'overtime'], function () {
        	Route::post('list', 'ApiHairStylistTimeOffOvertimeController@listOvertime');
        	Route::post('detail', 'ApiHairStylistTimeOffOvertimeController@detailOvertime');
        	Route::post('update', 'ApiHairStylistTimeOffOvertimeController@updateOvertime');
        	Route::post('create', 'ApiHairStylistTimeOffOvertimeController@createOvertime');
        	Route::post('delete', 'ApiHairStylistTimeOffOvertimeController@deleteOvertime');
    	});

    	Route::group(['prefix' => 'announcement'], function () {
        	Route::post('list', ['middleware' => 'feature_control:368,371', 'uses' =>'ApiAnnouncement@listAnnouncement']);
		    Route::post('detail', ['middleware' => 'feature_control:369', 'uses' =>'ApiAnnouncement@detailAnnouncement']);
		    Route::post('create', ['middleware' => 'feature_control:370', 'uses' =>'ApiAnnouncement@createAnnouncement']);
		    Route::post('delete', ['middleware' => 'feature_control:372', 'uses' =>'ApiAnnouncement@deleteAnnouncement']);
    	});

    	Route::group(['prefix' => 'update-data'], function () {
        	Route::post('list', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiMitraUpdateData@list']);
        	Route::post('detail', ['middleware' => 'feature_control:429', 'uses' => 'ApiMitraUpdateData@detail']);
        	Route::post('update', ['middleware' => 'feature_control:430', 'uses' => 'ApiMitraUpdateData@update']);
    	});

    	Route::group(['prefix' => 'group'], function () {
            Route::any('/', ['middleware' => 'feature_control:393','uses' =>'ApiHairStylistGroupController@index']);
            Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiHairStylistGroupController@create']);
            Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupController@update']);
            Route::post('detail', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@detail']);
            Route::post('create_commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@create_commission']);
            Route::post('update_commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@update_commission']);
            Route::post('detail_commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@detail_commission']);
            Route::post('product', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@product']);
            Route::post('hs', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@hs']);
            Route::post('invite_hs', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@invite_hs']);
            Route::post('commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@commission']);
            Route::post('list_hs', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@list_hs']);
            Route::any('list_group', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@list_group']);
            Route::any('list_default_insentif', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@list_default_insentif']);
            Route::any('list_default_potongan', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@list_default_potongan']);
            Route::any('setting_insentif', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@setting_insentif']);
            Route::any('setting_potongan', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@setting_potongan']);
            Route::group(['prefix' => 'insentif'], function () {
                Route::post('/', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupInsentifController@index']);
                Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiHairStylistGroupInsentifController@create']);
                Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupInsentifController@update']);
                Route::post('detail', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupInsentifController@detail']);
                Route::post('delete', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupInsentifController@delete']);
                Route::post('list_insentif', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupInsentifController@list_insentif']);
                Route::post('list-rumus-insentif', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupInsentifController@list_rumus_insentif']);
                Route::post('default/', ['middleware' => 'feature_control:425','uses' =>'ApiHairStylistGroupInsentifController@index_default']);
                Route::post('default/create', ['middleware' => 'feature_control:425','uses' =>'ApiHairStylistGroupInsentifController@create_default']);
                Route::post('default/update', ['middleware' => 'feature_control:425','uses' =>'ApiHairStylistGroupInsentifController@update_default']);
                Route::post('default/detail', ['middleware' => 'feature_control:425','uses' =>'ApiHairStylistGroupInsentifController@detail_default']);
                Route::post('default/delete', ['middleware' => 'feature_control:425','uses' =>'ApiHairStylistGroupInsentifController@delete_default']);
            });
            Route::group(['prefix' => 'potongan'], function () {
                Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiHairStylistGroupPotonganController@create']);
                Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupPotonganController@update']);
                Route::post('detail', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupPotonganController@detail']);
                Route::post('delete', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupPotonganController@delete']);
                Route::post('/', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupPotonganController@index']);
                Route::post('list_potongan', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupPotonganController@list_potongan']);
                Route::post('list-rumus-potongan', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupPotonganController@list_rumus_potongan']);
                Route::post('default/', ['middleware' => 'feature_control:426','uses' =>'ApiHairStylistGroupPotonganController@index_default']);
                Route::post('default/create', ['middleware' => 'feature_control:426','uses' =>'ApiHairStylistGroupPotonganController@create_default']);
                Route::post('default/update', ['middleware' => 'feature_control:426','uses' =>'ApiHairStylistGroupPotonganController@update_default']);
                Route::post('default/detail', ['middleware' => 'feature_control:426','uses' =>'ApiHairStylistGroupPotonganController@detail_default']);
                Route::post('default/delete', ['middleware' => 'feature_control:426','uses' =>'ApiHairStylistGroupPotonganController@delete_default']);
            });
    	});
        Route::any('attendance-setting','ApiHairstylistAttendanceController@setting');
        Route::group(['prefix' => 'attendance'], function () {
            Route::post('list','ApiHairstylistAttendanceController@list');
            Route::post('detail','ApiHairstylistAttendanceController@detail');
            Route::post('delete','ApiHairstylistAttendanceController@delete');
        });
        Route::group(['prefix' => 'attendance-pending'], function () {
            Route::post('list','ApiHairstylistAttendanceController@listPending');
            Route::post('detail','ApiHairstylistAttendanceController@detailPending');
            Route::post('update','ApiHairstylistAttendanceController@updatePending');
        });
        Route::group(['prefix' => 'attendance-request'], function () {
            Route::post('list','ApiHairstylistAttendanceController@listRequest');
            Route::post('detail','ApiHairstylistAttendanceController@detailRequest');
            Route::post('update','ApiHairstylistAttendanceController@updateRequest');
        });
        
    });
});

Route::group(['middleware' => ['log_activities_mitra_apps', 'user_agent'], 'prefix' => 'mitra'], function () {
    Route::get('splash','ApiMitra@splash');
    Route::group(['middleware' => ['auth_client', 'scopes:mitra-apps']], function()
    {
        Route::post('phone/check', 'ApiMitra@phoneCheck');
        Route::post('pin/forgot', 'ApiMitra@forgotPin');
        Route::post('pin/verify', 'ApiMitra@verifyPin')->middleware('decrypt_pin');
        Route::post('pin/change', 'ApiMitra@changePin')->middleware(['decrypt_pin:pin_new','decrypt_pin:pin_old']);
    });

    Route::group(['middleware' => ['auth:mitra', 'scopes:mitra-apps']], function () {
    	Route::get('announcement','ApiMitra@announcementList');
    	Route::any('home','ApiMitra@home');
        Route::any('logout','ApiMitra@logout');

    	Route::group(['prefix' => 'schedule'], function () {
        	Route::post('/', 'ApiMitra@schedule');
        	Route::post('create', 'ApiMitra@createSchedule');
    	});

		Route::group(['prefix' => 'outlet-service'], function () {
        	Route::get('detail', 'ApiMitraOutletService@outletServiceDetail');
            Route::post('customer/history', 'ApiMitraOutletService@customerHistory');
        	Route::post('customer/queue', 'ApiMitraOutletService@customerQueue');
        	Route::post('customer/detail', 'ApiMitraOutletService@customerQueueDetail');
        	Route::post('check-start', 'ApiMitraOutletService@checkStartService');
        	Route::post('start', 'ApiMitraOutletService@startService');
        	Route::post('stop', 'ApiMitraOutletService@stopService');
        	Route::post('check-extend', 'ApiMitraOutletService@checkExtendService');
        	Route::post('extend', 'ApiMitraOutletService@extendService');
        	Route::post('complete', 'ApiMitraOutletService@completeService');
        	Route::post('check-complete', 'ApiMitraOutletService@checkCompleteService');
        	Route::get('box', 'ApiMitraOutletService@availableBox');
            Route::post('payment-cash/detail', 'ApiMitraOutletService@paymentCashDetail');
            Route::post('payment-cash/completed', 'ApiMitraOutletService@paymentCashCompleted');
            Route::post('box', 'ApiMitraOutletService@selectBox');
    	});

    	Route::group(['prefix' => 'shop-service'], function () {
        	Route::post('detail', 'ApiMitraShopService@detailShopService');
        	Route::post('confirm', 'ApiMitraShopService@confirmShopService');
        	Route::post('history', 'ApiMitraShopService@historyShopService');
    	});

    	Route::group(['prefix' => 'inbox'], function () {
        	Route::post('marked', 'ApiMitraInbox@markedInbox');
		    Route::post('unmark', 'ApiMitraInbox@unmarkInbox');
		    Route::post('unread', 'ApiMitraInbox@unread');
        	Route::post('/{mode?}', 'ApiMitraInbox@listInbox');
    	});

		Route::group(['prefix' => 'rating'], function () {
        	Route::get('summary', 'ApiMitra@ratingSummary');
        	Route::get('comment', 'ApiMitra@ratingComment');
    	});

        Route::group(['prefix' => 'home-service'], function () {
            Route::post('update-location', 'ApiMitraHomeService@setHSLocation');
            Route::post('update-status', 'ApiMitraHomeService@activeInactiveHomeService');
            Route::post('list-order', 'ApiMitraHomeService@listOrder');
            Route::post('detail-order', 'ApiMitraHomeService@detailOrder');
            Route::post('detail-service', 'ApiMitraHomeService@detailOrderService');
            Route::post('action', 'ApiMitraHomeService@action');
            Route::post('history-order', 'ApiMitraHomeService@listHistoryOrder');
        });

        Route::group(['prefix' => 'data-update-request'], function () {
        	Route::get('/', 'ApiMitraUpdateData@listField');
        	Route::post('/', 'ApiMitraUpdateData@updateRequest');
    	});

        Route::get('balance-detail', 'ApiMitra@balanceDetail');
        Route::get('balance-history', 'ApiMitra@balanceHistory');
        Route::post('cash/transfer/detail', 'ApiMitra@transferCashDetail');
        Route::post('cash/transfer/create', 'ApiMitra@transferCashCreate');
        Route::post('cash/transfer/history', 'ApiMitra@transferCashHistory');
        Route::post('income-details', 'ApiMitra@commissionDetail');

        //Cash flow for SPV
        Route::post('income/detail', 'ApiMitra@incomeDetail');
        Route::post('acceptance/detail', 'ApiMitra@acceptanceDetail');
        Route::post('acceptance/confirm', 'ApiMitra@acceptanceConfirm');

        Route::post('cash/outlet/income/create', 'ApiMitra@outletIncomeCreate');
        Route::post('cash/outlet/transfer', 'ApiMitra@cashOutletTransfer');
        Route::post('cash/outlet/history', 'ApiMitra@cashOutletHistory');

        Route::post('expense/outlet/create', 'ApiMitra@expenseOutletCreate');
        Route::post('expense/outlet/history', 'ApiMitra@expenseOutletHistory');
        
        Route::group(['prefix' => 'income'], function () {
            Route::post('cron_middle', 'ApiIncome@cron_middle');
            Route::post('cron_end', 'ApiIncome@cron_end');
        });
	});

    Route::group(['middleware' => ['auth:mitra', 'scopes:mitra-apps'], 'prefix' => 'attendance'], function () {
        Route::get('live','ApiHairstylistAttendanceController@liveAttendance');
        Route::post('live','ApiHairstylistAttendanceController@storeLiveAttendance');
        Route::any('histories','ApiHairstylistAttendanceController@histories');
    });

    Route::group(['middleware' => ['auth:api'],'prefix' => 'request'], function () {
        Route::any('/', ['middleware'=>['feature_control:379','scopes:be'],'uses' => 'ApiRequestHairStylistController@index']);
        Route::any('/outlet', ['middleware'=>['feature_control:379','scopes:be'],'uses' => 'ApiRequestHairStylistController@listOutlet']);
        Route::any('/office', ['middleware'=>['feature_control:379','scopes:be'],'uses' => 'ApiRequestHairStylistController@listOffice']);
        Route::post('/create', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@store']);
        Route::post('/delete', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@destroy']);
        Route::post('/detail', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@show']);
        Route::post('/update', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@update']);
        Route::post('/list-hs', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@listHairStylistsOutlet']);
    });


});