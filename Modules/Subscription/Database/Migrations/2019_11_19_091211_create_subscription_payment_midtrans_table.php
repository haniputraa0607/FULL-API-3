<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionPaymentMidtransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_payment_midtrans', function (Blueprint $table) {
            $table->increments('id_subscription_payment');
            $table->integer('id_subscription')->unsigned()->index('fk_subscription_payments_subscription');
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
            
            $table->foreign('id_subscription', 'fk_subscription_payments_subscription')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_payment_midtrans');
    }
}
