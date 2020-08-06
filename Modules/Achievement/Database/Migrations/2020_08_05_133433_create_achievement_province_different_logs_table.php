<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementProvinceDifferentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achievement_province_different_logs', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_province_different_log');
            $table->bigInteger('id_achievement_group')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->integer('id_province')->nullable()->unsigned();
            $table->timestamps();

            $table->foreign('id_achievement_group', 'fk_achievement_province_different_logs_id_achievement_group')->references('id_achievement_group')->on('achievement_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_achievement_province_different_logs_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_province', 'fk_achievement_province_different_logs_id_province')->references('id_province')->on('provinces')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_province_different_logs');
    }
}
