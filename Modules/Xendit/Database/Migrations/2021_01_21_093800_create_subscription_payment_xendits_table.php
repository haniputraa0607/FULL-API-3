<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionPaymentXenditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_payment_xendits', function (Blueprint $table) {
            $table->increments('id_subscription_payment_xendit');
            $table->unsignedInteger('id_subscription');
            $table->unsignedInteger('id_subscription_user');
            $table->string('order_id')->nullable();
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
        Schema::dropIfExists('subscription_payment_xendits');
    }
}
