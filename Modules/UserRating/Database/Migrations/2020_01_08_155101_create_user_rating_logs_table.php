<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRatingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_rating_logs', function (Blueprint $table) {
            $table->increments('id_user_rating_log');
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
        Schema::dropIfExists('user_rating_logs');
    }
}
