<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimeToHairstylistScheduleDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_schedule_dates', function (Blueprint $table) {
            $table->unsignedBigInteger('id_hairstylist_attendance')->nullable()->after('id_hairstylist_schedule');
            $table->boolean('is_overtime')->default(0)->after('id_outlet_box');
            $table->time('clock_in')->default('00:00')->after('is_overtime');
            $table->time('clock_out')->default('00:00')->after('clock_in');
            $table->string('notes')->nullable()->after('clock_out');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_schedule_dates', function (Blueprint $table) {
            $table->dropColumn('id_hairstylist_attendance');
            $table->dropColumn('is_overtime');
            $table->dropColumn('clock_in');
            $table->dropColumn('clock_out');
            $table->dropColumn('notes');
        });
    }
}
