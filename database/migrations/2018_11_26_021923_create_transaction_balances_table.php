<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_balances', function (Blueprint $table) {
            $table->increments('id_transaction_balance');
            $table->string('receipt_number');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_transaction')->nullable();
            $table->integer('nominal');
            $table->string('approval_code');
            $table->dateTime('expired_at');
            $table->string('status')->default('Pending');
            $table->timestamps();

            $table->foreign('id_user', 'fk_transaction_balances_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_transaction_balances_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_transaction', 'fk_transaction_balances_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_balances');
    }
}
