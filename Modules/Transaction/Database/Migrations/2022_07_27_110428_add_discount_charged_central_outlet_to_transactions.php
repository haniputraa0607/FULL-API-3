<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountChargedCentralOutletToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('discount_charged_central', 30, 2)->default(0)->nullable()->after('transaction_discount_delivery');
            $table->decimal('discount_charged_outlet', 30, 2)->default(0)->nullable()->after('transaction_discount_delivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('discount_charged_central');
            $table->dropColumn('discount_charged_outlet');
        });
    }
}
