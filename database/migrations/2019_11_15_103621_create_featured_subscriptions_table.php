<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeaturedSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('featured_subscriptions', function (Blueprint $table) {
            $table->increments('id_featured_subscription');
            $table->integer('id_subscription')->unsigned();
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscriptions_featured_Subscriptions')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('featured_subscriptions');
    }
}
