<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_schedules', function (Blueprint $table) {
            $table->increments('id_outlet_schedule');
            $table->unsignedInteger('id_outlet');
            $table->string('day');
            $table->time('open');
            $table->time('close');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_outlet_schedule_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_schedules');
    }
}
