<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdHairstylistAttendanceToHairstylistAttendanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_attendance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('id_hairstylist_attendance')->after('id_hairstylist_attendance_log');
            $table->foreign('id_hairstylist_attendance', 'fk_iha_hal')->references('id_hairstylist_attendance')->on('hairstylist_attendances')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_attendance_logs', function (Blueprint $table) {
            $table->dropForeign('fk_iha_hal');
            $table->dropColumn('id_hairstylist_attendance');
        });
    }
}
