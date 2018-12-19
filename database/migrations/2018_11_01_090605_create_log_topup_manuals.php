<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTopupManuals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_topup_manuals', function (Blueprint $table) {
            $table->increments('id_log_topup_manual');
            $table->unsignedInteger('id_transaction')->default(0);
            $table->unsignedInteger('id_bank_method')->nullable();
            $table->unsignedInteger('id_bank')->nullable();
            $table->unsignedInteger('id_manual_payment')->nullable();
            $table->unsignedInteger('id_manual_payment_method')->nullable();
            $table->date('payment_date');
            $table->time('payment_time');
            $table->string('payment_bank');
            $table->string('payment_method');
            $table->string('payment_account_number');
            $table->string('payment_account_name');
            $table->integer('payment_nominal');
            $table->string('payment_receipt_image');
            $table->string('payment_note');
            $table->string('payment_note_confirm')->nullable();
            $table->string('confirmed_at')->nullable();
            $table->string('cancelled_at')->nullable();
            $table->string('id_user_confirming')->nullable();
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
        Schema::dropIfExists('log_topup_manuals');
    }
}
