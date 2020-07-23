<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionWelcomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_welcomes', function (Blueprint $table) {
            $table->bigIncrements('id_subscription_welcome');
            $table->unsignedInteger('id_subscription');
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscription_subscription_welcome')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_welcomes');
    }
}
