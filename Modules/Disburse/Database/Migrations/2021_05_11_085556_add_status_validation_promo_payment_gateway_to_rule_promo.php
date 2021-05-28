<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusValidationPromoPaymentGatewayToRulePromo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rule_promo_payment_gateway', function (Blueprint $table) {
            $table->smallInteger('validation_status')->default(0)->after('start_status');
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
            $table->dropColumn('validation_status');
        });
    }
}
