<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_attendances', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_attendance');
            $table->unsignedBigInteger('id_hairstylist_schedule_date')->nullable();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->time('clock_in_requirement');
            $table->time('clock_out_requirement');
            $table->integer('clock_in_tolerance');
            $table->integer('clock_out_tolerance');
            $table->boolean('is_on_time');
            $table->timestamps();

            $table->foreign('id_hairstylist_schedule_date')->on('hairstylist_schedule_dates')->references('id_hairstylist_schedule_date')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_attendances');
    }
}
