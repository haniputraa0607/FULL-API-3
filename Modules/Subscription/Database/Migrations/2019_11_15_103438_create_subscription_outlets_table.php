<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_outlets', function (Blueprint $table) {
            $table->increments('id_subscription_outlets');
            $table->integer('id_subscription')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscriptions_subscriptions_outlets')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_outlets_subscriptions_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_outlets');
    }
}
