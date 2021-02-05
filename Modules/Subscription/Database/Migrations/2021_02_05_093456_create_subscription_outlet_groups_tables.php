<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionOutletGroupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_outlet_groups', function (Blueprint $table) {
            $table->bigIncrements('id_subscription_outlet_group');
            $table->unsignedInteger('id_subscription');
            $table->unsignedInteger('id_outlet_group');
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscription_outlet_groups_subscription')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet_group', 'fk_subscription_outlet_groups_outlet_groups')->references('id_outlet_group')->on('outlet_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_outlet_groups');
    }
}
