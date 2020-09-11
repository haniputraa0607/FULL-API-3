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
        $schedule->call('Modules\Setting\Http\Controllers\ApiSetting@cronPointReset')->dailyAt('01:00');

        /**
         * detect transaction fraud and member balance by comparing the encryption of each data in the log_balances table
         * run every day at 02:00
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@checkSchedule')->dailyAt('02:00');

        /**
         * cancel all pending transaction that have been more than 15 minutes
         * run every hour
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@cron')->cron('*/15 * * * *');

        /**
         * reject all transactions that outlets do not receive within a certain timeframe
         * run every minute
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@autoReject')->cron('* * * * *');

        /**
         * cancel all pending deals that have been more than 15 minutes
         * run every hour
         */
        $schedule->call('Modules\Deals\Http\Controllers\ApiCronDealsController@cancel')->cron('*/1 * * * *');

        /**
         * cancel all pending subscription that have been more than 15 minutes
         * run every hour
         */
        $schedule->call('Modules\Subscription\Http\Controllers\ApiCronSubscriptionController@cancel')->cron('*/1 * * * *');

        /**
         * update all pickup transaction that have been more than 1 x 24 hours
         * run every day at 04:00
         */
        $schedule->call('Modules\Transaction\Http\Controllers\ApiCronTrxController@completeTransactionPickup')->dailyAt('05:00');

        /**
         * To process injection point
         * run every hour
         */
        $schedule->call('Modules\PointInjection\Http\Controllers\ApiPointInjectionController@getPointInjection')->hourly();

        /**
         * To process transaction sync from POS
         * Run every 2 minutes
         */
        $schedule->call('Modules\POS\Http\Controllers\ApiTransactionSync@transaction')->cron('*/2 * * * *');

        /**
         * To process sync menu outlets from the POS
         * Run every 3 minutes
         */
        $schedule->call('Modules\POS\Http\Controllers\ApiPOS@syncOutletMenuCron')->cron('*/3 * * * *');

        /**
         * To make daily transaction reports (offline and online transactions)
         * Run every day at 03:00
         */
        $schedule->call('Modules\Report\Http\Controllers\ApiCronReport@transactionCron')->dailyAt('03:00');

        /**
         * To process fraud
         */
        $schedule->call('Modules\SettingFraud\Http\Controllers\ApiFraud@fraudCron')->cron('*/59 * * * *');

        /**
         * reset notify outlet flag
         * run every day at 01:00
         */
        $schedule->call('Modules\Outlet\Http\Controllers\ApiOutletController@resetNotify')->dailyAt('00:30');

        /**
         * To process diburse
         */
        if(env('TYPE_CRON_DISBURSE') == 'monthly'){
            $schedule->call('Modules\Disburse\Http\Controllers\ApiIrisController@disburse')->monthlyOn(env('DAY_CRON_DISBURSE'), env('TIME_CRON_DISBURSE'));
        }elseif (env('TYPE_CRON_DISBURSE') == 'weekly'){
            $schedule->call('Modules\Disburse\Http\Controllers\ApiIrisController@disburse')->weeklyOn(env('DAY_WEEK_CRON_DISBURSE'), env('TIME_CRON_DISBURSE'));
        }elseif (env('TYPE_CRON_DISBURSE') == 'daily'){
            $schedule->call('Modules\Disburse\Http\Controllers\ApiIrisController@disburse')->dailyAt(env('TIME_CRON_DISBURSE'));
        }

        /**
         * To send email report trx
         */
        $schedule->call('Modules\Disburse\Http\Controllers\ApiDisburseController@cronSendEmailDisburse')->dailyAt('01:30');
        /**
         * Void failed transaction shopeepay
         */
        $schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronCancel')->cron('*/1 * * * *');
        /**
         * Void failed transaction deals shopeepay
         */
        $schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronCancelDeals')->cron('*/1 * * * *');
        /**
         * Void failed transaction subscription shopeepay
         */
        $schedule->call('Modules\ShopeePay\Http\Controllers\ShopeePayController@cronCancelSubscription')->cron('*/1 * * * *');

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
