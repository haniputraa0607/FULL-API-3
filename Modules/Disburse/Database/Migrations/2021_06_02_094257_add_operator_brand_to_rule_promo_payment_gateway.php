<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOperatorBrandToRulePromoPaymentGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->enum('operator_brand', ['or', 'and'])->nullable()->after('payment_gateway');
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
            $table->dropColumn('operator_brand');
        });
    }
}
