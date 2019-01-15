<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionMultiplePayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_multiple_payments', function (Blueprint $table) {
            $table->increments('id_transaction_multiple_payment');
            $table->unsignedInteger('id_transaction');
            $table->string('type');
            $table->unsignedInteger('id_payment');
            $table->timestamps();

            $table->index(["id_transaction"], 'fk_id_transaction_transaction_multiple_idx');
            $table->foreign('id_transaction', 'fk_id_transaction_transaction_multiple_idx')
                ->references('id_transaction')->on('transactions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_multiple_payments');
    }
}
