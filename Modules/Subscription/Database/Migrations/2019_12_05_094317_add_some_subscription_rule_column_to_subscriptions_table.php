<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeSubscriptionRuleColumnToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->smallInteger('daily_usage_limit')->nullable()->after('subscription_minimal_transaction');
            $table->enum('new_purchase_after', array('Empty','Expired'))->nullable()->after('daily_usage_limit');
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
            $table->dropColumn('daily_usage_limit');
            $table->dropColumn('new_purchase_after');
        });
    }
}
