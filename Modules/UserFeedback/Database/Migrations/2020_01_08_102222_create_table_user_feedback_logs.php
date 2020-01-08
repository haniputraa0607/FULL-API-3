<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUserFeedbackLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_feedback_logs', function (Blueprint $table) {
            $table->increments('id_user_feedback_log');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('refuse_count');
            $table->datetime('last_popup');
            $table->timestamps();

            $table->foreign('id_user')->on('users')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_feedback_logs');
    }
}
