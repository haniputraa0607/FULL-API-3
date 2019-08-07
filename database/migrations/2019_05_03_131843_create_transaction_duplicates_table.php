<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionDuplicatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_duplicates', function (Blueprint $table) {
            $table->increments('id_transaction_duplicate');
            $table->unsignedInteger('id_user')->nullable();
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_outlet_duplicate');
            $table->string('transaction_receipt_number');
            $table->string('outlet_code');
            $table->string('outlet_code_duplicate');
            $table->string('outlet_name');
            $table->string('outlet_name_duplicate');
            $table->string('user_name')->nullable();
            $table->string('user_phone')->nullable();
            $table->string('transaction_cashier')->nullable();
            $table->datetime('transaction_date');
            $table->decimal('transaction_subtotal',10,2)->nullable();
            $table->decimal('transaction_tax',10,2)->nullable();
            $table->decimal('transaction_service',10,2)->nullable();
            $table->decimal('transaction_grandtotal',10,2)->nullable();
            $table->datetime('sync_datetime');
            $table->datetime('sync_datetime_duplicate');
            $table->timestamps();

            $table->foreign('id_user', 'fk_transaction_duplicate_users')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_transaction', 'fk_transaction_duplicate_transactions')
                ->references('id_transaction')->on('transactions')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_outlet', 'fk_transaction_duplicate_outlets_1')
                ->references('id_outlet')->on('outlets')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_outlet_duplicate', 'fk_transaction_duplicate_outlets_2')
                ->references('id_outlet')->on('outlets')
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
        Schema::dropIfExists('transaction_duplicates');
    }
}
