<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoPaymentGatewayValidation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_payment_gateway_validation', function (Blueprint $table) {
            $table->bigIncrements('id_promo_payment_gateway_validation');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_rule_promo_payment_gateway');
            $table->date('start_date_periode')->nullable();
            $table->date('end_date_periode')->nullable();
            $table->integer('correct_get_promo')->default(0);
            $table->integer('not_get_promo')->default(0);
            $table->integer('must_get_promo')->default(0);
            $table->integer('wrong_cashback')->default(0);
            $table->string('file', 200)->nullable();
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
        Schema::dropIfExists('promo_payment_gateway_validation');
    }
}
