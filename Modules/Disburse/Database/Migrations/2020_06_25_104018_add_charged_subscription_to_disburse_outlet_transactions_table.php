<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChargedSubscriptionToDisburseOutletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->decimal('charged_subscription_central', 5,2)->nullable()->after('charged_promo_outlet');
            $table->decimal('charged_subscription_outlet', 5,2)->nullable()->after('charged_promo_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->dropColumn('charged_subscription_central');
            $table->dropColumn('charged_subscription_outlet');
        });
    }
}
