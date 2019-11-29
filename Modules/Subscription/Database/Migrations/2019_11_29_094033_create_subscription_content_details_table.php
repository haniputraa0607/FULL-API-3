<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionContentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_content_details', function (Blueprint $table) {
            $table->increments('id_subscription_content_detail');
            $table->integer('id_subscription_content')->unsigned();
            $table->text('content', 65535);
            $table->smallInteger('order');

            $table->timestamps();

            $table->foreign('id_subscription_content', 'fk_subscription_contents_subscriptions_content_details')->references('id_subscription_content')->on('subscription_contents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_content_details');
    }
}
