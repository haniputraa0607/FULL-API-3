<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRulePromoPaymentGatewayBrand extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rule_promo_payment_gateway_brand', function (Blueprint $table) {
            $table->bigIncrements('id_rule_promo_payment_gateway_brand');
            $table->unsignedInteger('id_rule_promo_payment_gateway');
            $table->unsignedInteger('id_brand');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('');
    }
}
