<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoPaymentGatewayValidationTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_payment_gateway_validation_transactions', function (Blueprint $table) {
            $table->bigIncrements('id_promo_payment_gateway_validation_transaction');
            $table->unsignedInteger('id_promo_payment_gateway_validation');
            $table->unsignedInteger('id_transaction');
            $table->string('validation_status', 100);
            $table->decimal('new_cashback', 30,2)->default(0);
            $table->decimal('old_cashback', 30,2)->default(0);
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
        Schema::dropIfExists('promo_payment_gateway_validation_transactions');
    }
}
