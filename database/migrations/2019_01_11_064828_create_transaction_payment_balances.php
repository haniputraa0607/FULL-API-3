<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentBalances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_balances', function (Blueprint $table) {
            $table->increments('id_transaction_payment_balance');
            $table->unsignedInteger('id_transaction');
            $table->string('balance_nominal');
            $table->timestamps();

            $table->index(["id_transaction"], 'fk_id_transaction_transaction_payment_balance_idx');
            $table->foreign('id_transaction', 'fk_id_transaction_transaction_payment_balance_idx')
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
        Schema::dropIfExists('transaction_payment_balances');
    }
}
