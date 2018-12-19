<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewardUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reward_users', function (Blueprint $table) {
            $table->increments('id_reward_user');
            $table->unsignedInteger('id_reward');
            $table->unsignedInteger('id_user');
            $table->integer('total_coupon');
            $table->timestamps();

            $table->foreign('id_reward', 'fk_reward_users_rewards')->references('id_reward')->on('rewards')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_reward_users_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reward_users');
    }
}
