<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_contents', function (Blueprint $table) {
            $table->increments('id_subscription_content');
            $table->integer('id_subscription')->unsigned();
            $table->string('title', 50);
            $table->smallInteger('order');
            $table->boolean('is_active')->nullable()->default(1);

            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscriptions_subscriptions_contents')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_contents');
    }
}
