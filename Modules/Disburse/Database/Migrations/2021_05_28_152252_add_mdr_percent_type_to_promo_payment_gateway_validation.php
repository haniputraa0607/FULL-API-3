<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMdrPercentTypeToPromoPaymentGatewayValidation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->enum('override_mdr_percent_type', ['Percent', 'Nominal'])->nullable()->after('validation_cashback_type');
            $table->smallInteger('override_mdr_status')->nullable()->default(0)->after('validation_cashback_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->dropColumn('override_mdr_percent_type');
            $table->dropColumn('override_mdr_status');
        });
    }
}
