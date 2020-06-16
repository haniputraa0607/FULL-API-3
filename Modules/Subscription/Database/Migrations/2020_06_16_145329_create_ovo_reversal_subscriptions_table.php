<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOvoReversalSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ovo_reversal_subscriptions', function (Blueprint $table) {
            $table->increments('id_ovo_reversal_subscription');
            $table->integer('id_subscription_user')->unsigned();
            $table->integer('id_subscription_payment_ovo')->unsigned();
            $table->datetime('date_push_to_pay');
            $table->text('request');
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
        Schema::dropIfExists('ovo_reversal_subscriptions');
    }
}
