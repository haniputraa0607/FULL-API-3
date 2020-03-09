<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achievement_users', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_user');
            $table->bigInteger('id_achievement_detail')->unsigned();
            $table->integer('id_user')->unsigned();
            $table->longText('json_rule');
            $table->longText('json_rule_enc');
            $table->timestamp('date');
            $table->timestamps();
            
            $table->foreign('id_achievement_detail', 'fk_achievement_users_id_achievement_detail')->references('id_achievement_detail')->on('achievement_details')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_achievement_users_id_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_users');
    }
}
