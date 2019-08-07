<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentOvosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_ovos', function (Blueprint $table) {
            $table->increments('id_transaction_payment_ovo');
            $table->unsignedInteger('id_transaction');
            $table->integer('amount');
            $table->string('trace_number')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('response_code')->nullable();
            $table->string('batch_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('ovoid')->nullable();
            $table->integer('cash_used')->nullable();
            $table->integer('ovo_points_earned')->nullable();
            $table->integer('cash_balance')->nullable();
            $table->integer('full_name')->nullable();
            $table->integer('ovo_points_used')->nullable();
            $table->integer('ovo_points_balance')->nullable();
            $table->string('payment_type')->nullable();
            $table->timestamps();

            $table->index(["id_transaction"], 'fk_id_transaction_transaction_payment_ovo_idx');
            $table->foreign('id_transaction', 'fk_id_transaction_transaction_payment_ovo_idx')
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
        Schema::dropIfExists('transaction_payment_ovos');
    }
}
