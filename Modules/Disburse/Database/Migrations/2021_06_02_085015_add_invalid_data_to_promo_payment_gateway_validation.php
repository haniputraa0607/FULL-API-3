<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvalidDataToPromoPaymentGatewayValidation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->integer('invalid_data')->default(0)->after('end_date_periode');
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
            $table->dropColumn('invalid_data');
        });
    }
}
