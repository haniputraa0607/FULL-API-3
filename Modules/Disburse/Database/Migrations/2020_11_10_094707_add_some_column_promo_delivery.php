<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnPromoDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->decimal('discount_delivery_central', 30, 4)->default(0)->after('subscription_central');
            $table->decimal('discount_delivery_outlet', 30, 4)->default(0)->after('subscription_central');
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
            $table->dropColumn('discount_delivery_central');
            $table->dropColumn('discount_delivery_outlet');
        });
    }
}
