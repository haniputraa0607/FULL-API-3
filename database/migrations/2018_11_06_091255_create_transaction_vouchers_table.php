<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_vouchers', function (Blueprint $table) {
            $table->increments('id_transaction_voucher');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_deals_voucher');
            $table->timestamps();

            $table->foreign('id_transaction', 'fk_transaction_vouchers_transactions')
            ->references('id_transaction')->on('transactions')
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->foreign('id_deals_voucher', 'fk_transaction_vouchers_deals_vouchers')
            ->references('id_deals_voucher')->on('deals_vouchers')
            ->onUpdate('cascade')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_vouchers');
    }
}
