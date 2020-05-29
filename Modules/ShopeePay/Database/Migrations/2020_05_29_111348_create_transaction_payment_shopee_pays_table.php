<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentShopeePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_shopee_pays', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_payment_shopee_pay');
            $table->unsignedInteger('id_transaction');
            $table->string('request_id')->nullable();
            $table->string('payment_reference_id')->nullable();
            $table->string('merchant_ext_id')->nullable();
            $table->string('store_ext_id')->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->string('currency')->nullable();
            $table->string('return_url')->nullable();
            $table->string('point_of_initiation')->default('app');
            $table->string('validity_period')->nullable();
            $table->string('additional_info')->nullable();
            $table->string('transaction_sn')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('user_id_hash')->nullable();
            $table->string('terminal_id')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction')->references('id_transaction')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_payment_shopee_pays');
    }
}
