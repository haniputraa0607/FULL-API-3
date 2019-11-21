<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionVoucherPercentToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('subscription_voucher_nominal')->nullable(true)->change();
            $table->integer('subscription_voucher_percent')->nullable()->after('subscription_voucher_nominal');
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
            $table->integer('subscription_voucher_nominal')->nullable(false)->change();
            $table->dropColumn('subscription_voucher_percent');
        });
    }
}
