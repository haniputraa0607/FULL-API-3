<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPaymentShopeePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_payment_shopee_pays', function (Blueprint $table) {
            $table->bigIncrements('id_deals_payment_shopee_pay');
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_deals_user');
            $table->string('order_id')->nullable();
            $table->string('request_id')->nullable();
            $table->string('payment_reference_id')->nullable();
            $table->string('merchant_ext_id')->nullable();
            $table->string('store_ext_id')->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->string('currency')->nullable();
            $table->string('return_url')->nullable();
            $table->string('point_of_initiation')->default('app');
            $table->string('validity_period')->nullable();
            $table->string('additional_info')->nullable();
            $table->string('transaction_sn')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('user_id_hash')->nullable();
            $table->string('terminal_id')->nullable();
            $table->timestamps();

            $table->foreign('id_deals')->references('id_deals')->on('deals')->onDelete('cascade');
            $table->foreign('id_deals_user')->references('id_deals_user')->on('deals_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_payment_shopee_pays');
    }
}
