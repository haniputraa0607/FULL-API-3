<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaximumTotalCashbackToRulePromoPaymentGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->decimal('maximum_total_cashback', 30, 2)->default(0)->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->dropColumn('maximum_total_cashback');
        });
    }
}
