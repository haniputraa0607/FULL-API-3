<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestUserDetailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quest_user_detail_logs', function (Blueprint $table) {
            $table->bigIncrements('id_quest_user_detail_log');
            $table->bigInteger('id_quest')->unsigned();
            $table->bigInteger('id_quest_detail')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->longText('json_rule');
            $table->longText('json_rule_enc');
            $table->timestamp('date');
            $table->timestamps();

            $table->foreign('id_quest', 'fk_quest_user_detail_logs_id_quest')->references('id_quest')->on('quests')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_quest_detail', 'fk_quest_user_detail_logs_id_quest_detail')->references('id_quest_detail')->on('quest_details')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_quest_user_detail_logs_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quest_user_detail_logs');
    }
}
