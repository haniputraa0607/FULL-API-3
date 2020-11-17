<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionPaymentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_payment_methods', function (Blueprint $table) {
            $table->increments('id_subscription_payment_method');
            $table->unsignedInteger('id_subscription');
            $table->string('payment_method');
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscription_payment_methods_subscription')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_payment_methods');
    }
}
