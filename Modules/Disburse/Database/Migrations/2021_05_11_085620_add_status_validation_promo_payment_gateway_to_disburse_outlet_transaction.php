<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusValidationPromoPaymentGatewayToDisburseOutletTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->smallInteger('status_validation_promo_payment_gateway')->nullable()->default(0)->after('id_rule_promo_payment_gateway');
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
            $table->dropColumn('status_validation_promo_payment_gateway');
        });
    }
}
