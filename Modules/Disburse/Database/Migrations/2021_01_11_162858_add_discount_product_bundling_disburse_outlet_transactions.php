<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountProductBundlingDisburseOutletTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->decimal('bundling_product_fee_central', 30,4)->default(0)->after('subscription_central');
            $table->decimal('bundling_product_fee_outlet', 30,4)->default(0)->after('subscription_central');
            $table->decimal('bundling_product_total_discount', 30,4)->default(0)->after('subscription_central');
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
            $table->dropColumn('bundling_product_fee_central');
            $table->dropColumn('bundling_product_fee_outlet');
            $table->dropColumn('bundling_product_total_discount');
        });
    }
}
