<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementOutletLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achievement_outlet_logs', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_outlet_log');
            $table->bigInteger('id_achievement_group')->unsigned();
            $table->bigInteger('id_achievement_detail')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->integer('id_outlet')->nullable()->unsigned();
            $table->integer('product_total')->nullable();
            $table->integer('product_nominal')->nullable();
            $table->integer('count')->nullable();
            $table->timestamp('date')->nullable();
            $table->text('enc')->nullable();
            $table->timestamps();

            $table->foreign('id_achievement_group', 'fk_achievement_outlet_logs_id_achievement_group')->references('id_achievement_group')->on('achievement_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_achievement_detail', 'fk_achievement_outlet_logs_id_achievement_detail')->references('id_achievement_detail')->on('achievement_details')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_achievement_outlet_logs_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_achievement_outlet_logs_id_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_outlet_logs');
    }
}
