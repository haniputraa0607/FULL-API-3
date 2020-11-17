<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionShipmentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_shipment_methods', function (Blueprint $table) {
            $table->increments('id_subscription_shipment_method');
            $table->unsignedInteger('id_subscription');
            $table->string('shipment_method');
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscription_shipment_methods_subscription')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_shipment_methods');
    }
}
