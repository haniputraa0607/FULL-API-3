<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnSubscriptionCentral extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->decimal('subscription_central', 30, 4)->default(0)->after('subscription');
            $table->decimal('discount_central', 30, 4)->default(0)->after('discount');
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
            $table->dropColumn('subscription_central');
            $table->dropColumn('discount_central');
        });
    }
}
