<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionVoucherDurationStartExpiredToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('subscription_voucher_duration')->nullable()->after('user_limit');
            $table->dateTime('subscription_voucher_start')->nullable()->after('subscription_voucher_duration');
            $table->dateTime('subscription_voucher_expired')->nullable()->after('subscription_voucher_start');
            $table->dropColumn('subscription_day_valid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('subscription_day_valid')->after('user_limit');
            $table->dropColumn('subscription_voucher_duration');
            $table->dropColumn('subscription_voucher_start');
            $table->dropColumn('subscription_voucher_expired');
        });
    }
}
