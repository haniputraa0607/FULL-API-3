<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLogTopupMidtrans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_topup_midtrans', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id_log_topup_midtrans');
            $table->unsignedInteger('id_log_topup')->foreign('id_log_topup', 'fk_log_topup')
                            ->references('id_log_topup')->on('log_topups')
                            ->onDelete('cascade')
                            ->onUpdate('cascade');
            
            $table->string('masked_card', 191)->nullable();
            $table->string('approval_code', 191)->nullable();
            $table->string('bank', 191)->nullable();
            $table->string('eci', 191)->nullable();
            $table->string('transaction_time', 191)->nullable();
            $table->string('gross_amount', 191);
            $table->string('order_id', 191);
            $table->string('payment_type', 191)->nullable();
            $table->string('signature_key', 191)->nullable();
            $table->string('status_code', 191)->nullable();
            $table->string('vt_transaction_id', 191)->nullable();
            $table->string('transaction_status', 191)->nullable();
            $table->string('fraud_status', 191)->nullable();
            $table->string('status_message', 191)->nullable();
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
        Schema::drop('log_topup_midtrans');
    }
}
