<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserColumnPriceMethodBalanceStatusToSubscriptionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->integer('subscription_price_point')->nullable()->after('subscription_expired_at');
            $table->integer('subscription_price_cash')->nullable()->after('subscription_price_point');
            $table->enum('payment_method', array('Manual','Midtrans','Offline','Balance'))->nullable()->after('subscription_price_cash');
            $table->enum('paid_status', array('Free','Pending','Paid','Completed','Cancelled'))->default('Pending')->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->dropColumn('subscription_price_point');
            $table->dropColumn('subscription_price_cash');
            $table->dropColumn('payment_method');
            $table->dropColumn('paid_status');
        });
    }
}
