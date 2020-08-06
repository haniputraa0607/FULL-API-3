<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementOutletDifferentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achievement_outlet_different_logs', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_outlet_different_log');
            $table->bigInteger('id_achievement_group')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->integer('id_outlet')->nullable()->unsigned();
            $table->timestamps();

            $table->foreign('id_achievement_group', 'fk_achievement_outlet_different_logs_id_achievement_group')->references('id_achievement_group')->on('achievement_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_achievement_outlet_different_logs_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_achievement_outlet_different_logs_id_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_outlet_different_logs');
    }
}
