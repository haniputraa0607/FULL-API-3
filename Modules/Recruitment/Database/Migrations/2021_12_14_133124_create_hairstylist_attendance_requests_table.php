<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_attendance_requests', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_attendance_request');
            $table->unsignedBigInteger('id_user_hair_stylist');
            $table->unsignedBigInteger('id_hairstylist_schedule_date');
            $table->time('clock_in');
            $table->time('clock_out');
            $table->string('notes');
            $table->enum('status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
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
        Schema::dropIfExists('hairstylist_attendance_requests');
    }
}
