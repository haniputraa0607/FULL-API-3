<?php
Route::group(['prefix' => 'api/outlet', 'middleware' => 'log_request', 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('list', 'ApiOutletController@listOutlet');
    Route::any('list/gofood', 'ApiOutletGofoodController@listOutletGofood');
    Route::any('filter', 'ApiOutletController@filter');
});

Route::group(['prefix' => 'api/outlet', 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    /**
     * outlet
     */
    Route::group(['middleware' => 'auth_client'], function() {
        Route::get('city', 'ApiOutletController@cityOutlet');
        Route::any('nearme', 'ApiOutletController@nearMe');
        // Route::any('filter', 'ApiOutletController@filter');
        Route::any('nearme/geolocation', 'ApiOutletController@nearMeGeolocation');
        Route::any('filter/geolocation', 'ApiOutletController@filterGeolocation');
        Route::any('schedule/save', 'ApiOutletController@scheduleSave');
        /* SYNC */
        Route::any('sync', 'ApiSyncOutletController@sync');
    });

    Route::group(['middleware' => 'auth:api'], function() {
        Route::post('create', 'ApiOutletController@create');
        Route::post('update', 'ApiOutletController@update');
        Route::post('update/status', 'ApiOutletController@updateStatus');
        Route::post('update/pin', 'ApiOutletController@updatePin');
        Route::post('delete', 'ApiOutletController@delete');
        Route::get('export', 'ApiOutletController@export');
        Route::post('import', 'ApiOutletController@import');

        /**
         * photo
         */
        Route::group(['prefix' => 'photo'], function() {
            Route::post('create', 'ApiOutletController@upload');
            Route::post('update', 'ApiOutletController@updatePhoto');
            Route::post('delete', 'ApiOutletController@deleteUpload');
        });

        /**
         * holiday
         */
        Route::group(['prefix' => 'holiday'], function() {
            Route::any('list', 'ApiOutletController@listHoliday');
            Route::post('create', 'ApiOutletController@createHoliday');
            Route::post('update', 'ApiOutletController@updateHoliday');
            Route::post('delete', 'ApiOutletController@deleteHoliday');
        });

        // admin outlet
        Route::group(['prefix' => 'admin'], function() {
            Route::post('create', 'ApiOutletController@createAdminOutlet');
            Route::post('detail', 'ApiOutletController@detailAdminOutlet');
            Route::post('update', 'ApiOutletController@updateAdminOutlet');
            Route::post('delete', 'ApiOutletController@deleteAdminOutlet');
        });
        
    });
});
