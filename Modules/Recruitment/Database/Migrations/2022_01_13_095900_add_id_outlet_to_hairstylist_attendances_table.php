<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletToHairstylistAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_attendances', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet')->nullable()->after('id_user_hair_stylist');
            $table->foreign('id_outlet', 'fk_id_outlet_ha_o')->references('id_outlet')->on('outlets')->onDelete('cascade');
        });
        Schema::table('hairstylist_attendance_requests', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet')->nullable()->after('id_user_hair_stylist');
            $table->foreign('id_outlet', 'fk_id_outlet_har_o')->references('id_outlet')->on('outlets')->onDelete('cascade');
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
            $table->dropForeign('fk_id_outlet_ha_o');
            $table->dropColumn('id_outlet');
        });
        Schema::table('hairstylist_attendance_requests', function (Blueprint $table) {
            $table->dropForeign('fk_id_outlet_har_o');
            $table->dropColumn('id_outlet');
        });
    }
}
