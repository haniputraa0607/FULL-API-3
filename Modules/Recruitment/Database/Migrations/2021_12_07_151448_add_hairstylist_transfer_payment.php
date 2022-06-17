<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHairstylistTransferPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_transfer_payments', function (Blueprint $table) {
            $table->integerIncrements('id_hairstylist_transfer_payment');
            $table->unsignedInteger('id_hairstylist_log_balance');
            $table->unsignedInteger('id_user_hair_stylist');
            $table->unsignedInteger('id_outlet');
            $table->string('transfer_payment_code');
            $table->enum('transfer_payment_status', ['Pending', 'Confirm'])->default('Pending');
            $table->integer('total_amount');
            $table->dateTime('confirm_at')->nullable();
            $table->integer('confirm_by')->nullable();
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
        Schema::dropIfExists('hairstylist_transfer_payments');
    }
}
