<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoPaymentGatewayTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_payment_gateway_transactions', function (Blueprint $table) {
            $table->bigIncrements('id_promo_payment_gateway_transaction');
            $table->unsignedInteger('id_rule_promo_payment_gateway');
            $table->string('payment_gateway_user', 100)->nullable();
            $table->unsignedInteger('id_user')->nullable();
            $table->unsignedInteger('id_transaction');
            $table->decimal('total_received_cashback', 30,2)->default(0);
            $table->smallInteger('status_active')->default(1);
            $table->timestamps();
            $table->index('payment_gateway_user');
            $table->index('id_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_payment_gateway_transactions');
    }
}
