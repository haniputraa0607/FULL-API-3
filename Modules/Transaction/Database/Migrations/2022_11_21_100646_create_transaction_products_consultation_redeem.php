<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionProductsConsultationRedeem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_consultation_redeem', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_product_consultation_redeem');
            $table->integer('id_transaction_product');
            $table->integer('id_transaction_consultation_recomendation');
            $table->integer('qty');
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
        Schema::dropIfExists('transaction_product_consultation_redeem');
    }
}
