<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestUserRedemptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quest_user_redemptions', function (Blueprint $table) {
            $table->bigIncrements('id_quest_user_redemption');
            $table->unsignedInteger('id_user');
            $table->unsignedBigInteger('id_quest');
            $table->boolean('redemption_status');
            $table->dateTime('redemption_date');
            $table->timestamps();

            $table->foreign('id_quest')->references('id_quest')->on('quests')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quest_user_redemptions');
    }
}
