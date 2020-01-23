<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_users', function (Blueprint $table) {
            $table->increments('id_subscription_user');
            $table->integer('id_user')->unsigned();
            $table->integer('id_subscription')->unsigned();
            $table->dateTime('bought_at');
            $table->dateTime('subscription_expired_at');
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscriptions_subscriptions_users')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->foreign('id_user', 'fk_users_subscriptions_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_users');
    }
}
