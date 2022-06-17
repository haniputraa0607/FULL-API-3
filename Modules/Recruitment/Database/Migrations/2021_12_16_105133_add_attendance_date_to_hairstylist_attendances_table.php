<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttendanceDateToHairstylistAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_attendances', function (Blueprint $table) {
            $table->date('attendance_date')->after('id_hairstylist_schedule_date');
            $table->unsignedBigInteger('id_user_hair_stylist')->after('id_hairstylist_schedule_date');
            $table->foreign('id_user_hair_stylist', 'fk_iuh_ha')->references('id_user_hair_stylist')->on('user_hair_stylist')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_attendances', function (Blueprint $table) {
            $table->dropForeign('fk_iuh_ha');
            $table->dropColumn('id_user_hair_stylist');
            $table->dropColumn('attendance_date');
        });
    }
}
