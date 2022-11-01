<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * sending the campaign schedule
         * run every 5 minute
         */
        $schedule->call('Modules\Campaign\Http\Controllers\ApiCampaign@insertQueue')->everyFiveMinutes();

        /**
         * insert the promotion data that must be sent to the promotion_queue table
         * run every 5 minute
         */
        $schedule->call('Modules\Promotion\Http\Controllers\ApiPromotion@addPromotionQueue')->everyFiveMinutes();

        /**
         * send 100 data from the promotion_queue table
         * run every 6 minute
         */
        $schedule->call('Modules\Promotion\Http\Controllers\ApiPromotion@sendPromotion')->cron('*/6 * * * *');

        /**
         * reset all member points / balance
         * run every day at 01:00
         */
        $schedule->call('Modules\Setting\Http\Controllers\ApiSetting@cronPointReset')->dailyAt(config('app.env') == 'staging' ? '05:15' : '01:00');

        /**
         * detect transaction fraud and member balance by comparing the encryption of each data in the log_balances table
         * run every day at 02:00
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@checkSchedule')->dailyAt(config('app.env') == 'staging' ? '05:45' : '04:30');

        /**
         * cancel all pending transaction that have been more than 5 minutes
         * run every 5 minute
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@cron')->cron('*/5 * * * *');

        /**
         * reject all transactions that outlets do not receive within a certain timeframe
         * run every minute
         */
        //$schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@autoReject')->cron('* * * * *');

        /**
         * set ready order that outlets do not ready within 5 minutes after pickup_at
         * run every minute
         */
        //$schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@autoReadyOrder')->cron('* * * * *');

        /**
         * cancel all pending deals that have been more than 5 minutes
         * run every 2 minute
         */
        //$schedule->call('Modules\Deals\Http\Controllers\ApiCronDealsController@cancel')->cron('*/2 * * * *');

        /**
         * cancel all pending subscription that have been more than 5 minutes
         * run every 2 minute
         */
        //$schedule->call('Modules\Subscription\Http\Controllers\ApiCronSubscriptionController@cancel')->cron('*/2 * * * *');

        /**
         * update all pickup transaction that have been more than 1 x 24 hours
         * run every day at 04:00
         */
        //$schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@completeTransactionPickup')->dailyAt('23:50');

        /**
         * calculate achievement all transaction that have not calculated the achievement
         * run every day at 05:00
         */
        $schedule->call('Modules\Achievement\Http\Controllers\ApiAchievement@calculateAchievement')->dailyAt(config('app.env') == 'staging' ? '06:00' : '05:00');

        /**
         * To process injection point
         * run every hour
         */
        $schedule->call('Modules\PointInjection\Http\Controllers\ApiPointInjectionController@getPointInjection')->hourly();

        /**
         * To process transaction sync from POS
         * Run every 2 minutes
         */
        // $schedule->call('Modules\POS\Http\Controllers\ApiTransactionSync@transaction')->cron('*/2 * * * *');

        /**
         * To process sync menu outlets from the POS
         * Run every 3 minutes
         */
        // $schedule->call('Modules\POS\Http\Controllers\ApiPOS@syncOutletMenuCron')->cron('*/3 * * * *');

        /**
         * To make daily transaction reports (offline and online transactions)
         * Run every day at 03:00
         */
        $schedule->call('Modules\Report\Http\Controllers\ApiCronReport@transactionCron')->dailyAt(config('app.env') == 'staging' ? '05:40' : '03:00');

        /**
         * To process fraud
         */
        $schedule->call('Modules\SettingFraud\Http\Controllers\ApiFraud@fraudCron')->cron('*/59 * * * *');

        /**
         * reset notify outlet flag
         * run every day at 01:00
         */
        $schedule->call('Modules\Outlet\Http\Controllers\ApiOutletController@resetNotify')->dailyAt(config('app.env') == 'staging' ? '05:50' : '00:30');

        /**
         * To process diburse
         */
//        if(env('TYPE_CRON_DISBURSE') == 'monthly'){
//            $schedule->call('Modules\Disburse\Http\Controllers\ApiIrisController@disburse')->monthlyOn(env('DAY_CRON_DISBURSE'), env('TIME_CRON_DISBURSE'));
//        }elseif (env('TYPE_CRON_DISBURSE') == 'weekly'){
//            $schedule->call('Modules\Disburse\Http\Controllers\ApiIrisController@disburse')->weeklyOn(env('DAY_WEEK_CRON_DISBURSE'), env('TIME_CRON_DISBURSE'));
//        }elseif (env('TYPE_CRON_DISBURSE') == 'daily'){
//            $schedule->call('Modules\Disburse\Http\Controllers\ApiIrisController@disburse')->dailyAt(env('TIME_CRON_DISBURSE'));
//        }

        /**
         * To send email report trx
         */
        //$schedule->call('Modules\Disburse\Http\Controllers\ApiDisburseController@cronSendEmailDisburse')->dailyAt('02:00');
        /**
         * To send
         */
        //$schedule->call('Modules\Disburse\Http\Controllers\ApiDisburseController@shortcutRecap')->dailyAt('02:30');
        /**
         * Void failed transaction shopeepay
         */
        //$schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronCancel')->cron('*/1 * * * *');
        /**
         * Void failed transaction deals shopeepay
         */
        //$schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronCancelDeals')->cron('*/1 * * * *');
        /**
         * Void failed transaction subscription shopeepay
         */
        //$schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronCancelSubscription')->cron('*/1 * * * *');

        /**
         * process refund shopeepay at 06:00
         */
        //$schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronRefund')->dailyAt(config('app.env') == 'staging' ? '05:37' : '03:05');

        /**
         * Check the status of Gosend which is not updated after 5 minutes
         * run every 3 minutes
         */
        //$schedule->call('Modules\Transaction\Http\Controllers\ApiGosendController@cronCheckStatus')->cron('*/3 * * * *');

        /**
         * Check the status of Wehelpyou which is not updated after 3 minutes
         * run every 1 minutes
         */
        //$schedule->call('Modules\Transaction\Http\Controllers\ApiWehelpyouController@cronCheckStatus')->cron('*/1 * * * *');

        /**
         * Auto reject order when driver not found > 30minutes
         * run every 5 minutes
         */
        //$schedule->call('Modules\OutletApp\Http\Controllers\ApiOutletApp@cronDriverNotFound')->cron('*/1 * * * *');

        /**
         * Notif Order not Received/Rejected 
         * run every minute
         */
        //$schedule->call('Modules\OutletApp\Http\Controllers\ApiOutletApp@cronNotReceived')->everyMinute();

        /**
         * Sync Bundling
         * run every day at
         */
        //$schedule->call('Modules\ProductBundling\Http\Controllers\ApiBundlingController@bundlingToday')->dailyAt(config('app.env') == 'staging' ? '05:16' : '04:00');

        /**
         * Auto reject order wehelpyou when driver not found > 10 minutes
         * run every 1 minutes
         */
        //$schedule->call('Modules\Transaction\Http\Controllers\ApiWehelpyouController@cronCancelDelivery')->cron('*/1 * * * *');

        /**
         * Get Tracking status
         * run every 15 minutes
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiShipperController@updateTrackingTransaction')->cron('*/15 * * * *');

        /**
         * Update satus received
         * run every 4 am
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiShipperController@cronCompletedReceivedOrder')->dailyAt(config('app.env') == 'staging' ? '06:00' : '04:00');

        /**
         * generate token value first
         * run every day at 06:30
         */
        $schedule->call('Modules\Users\Http\Controllers\ApiLoginRegisterV2@generateToken')->dailyAt('11:12');

        /**
         * To backup and truncate log database
         */
        $schedule->command('backup:logdb --table=log_activities_apps --table=log_activities_be --table=log_activities_outlet_apps --table=log_activities_pos --table=log_activities_pos_transaction --truncate --chunk=10000')->dailyAt(config('app.env') == 'staging' ? '05:17' : '00:20');

        $schedule->command('backup:logdb --table=log_api_gosends --table=log_api_wehelpyou --table=log_backend_errors --table=log_call_outlet_apps --table=log_check_promo_code --table=log_crons --table=log_ipay88s --table=log_iris --table=log_midtrans --table=log_ovo_deals --table=log_ovos --table=log_shopee_pays --table=log_xendits --truncate')->dailyAt(config('app.env') == 'staging' ? '05:18' : '00:25');

        /**
         * AutoEnd Consultation
         * run every minute
         */
        $schedule->call('Modules\Consultation\Http\Controllers\ApiTransactionConsultationController@cronAutoEndConsultation')->everyMinute();

        /**
         * Update doctor status to online or offline 
         * run every 5 minute
         */
        $schedule->call('Modules\Doctor\Http\Controllers\ApiDoctorController@cronUpdateDoctorStatus')->everyMinute();

        /**
         * Auto cancel cancel
         * run every day at 00:05
         */
        $schedule->call('Modules\Merchant\Http\Controllers\ApiMerchantTransactionController@autoCancel')->dailyAt(config('app.env') == 'staging' ? '10:52' : '00:06');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
