<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlingPeriodeDay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling_periode_day', function (Blueprint $table) {
            $table->bigIncrements('id_bundling_periode_day');
            $table->unsignedInteger('id_bundling');
            $table->string('day');
            $table->time('time_start');
            $table->time('time_end');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bundling_periode_day');
    }
}
