<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentXenditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payment_xendits', function (Blueprint $table) {
            $table->increments('id_transaction_payment_xendit');
            $table->unsignedInteger('id_transaction')->nullable();
            $table->unsignedInteger('id_transaction_group')->nullable();
            $table->string('xendit_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('business_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->nullable();
            $table->string('amount')->nullable();
            $table->string('expiration_date')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('status')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction')->references('id_transaction')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_payment_xendits');
    }
}
