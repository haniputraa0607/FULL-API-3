<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionPaymentShopeePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_payment_shopee_pays', function (Blueprint $table) {
            $table->increments('id_subscription_payment_shopee_pay');
            $table->unsignedInteger('id_subscription');
            $table->unsignedInteger('id_subscription_user');
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
            $table->string('redirect_url_app')->nullable();
            $table->string('redirect_url_http')->nullable();
            $table->string('refund_reference_id')->nullable();
            $table->string('void_reference_id')->nullable();
            $table->string('errcode')->nullable();
            $table->string('err_reason')->nullable();
            $table->timestamps();

            $table->foreign('id_subscription')->references('id_subscription')->on('subscriptions')->onDelete('cascade');
            $table->foreign('id_subscription_user')->references('id_subscription_user')->on('subscription_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_payment_shopee_pays');
    }
}
