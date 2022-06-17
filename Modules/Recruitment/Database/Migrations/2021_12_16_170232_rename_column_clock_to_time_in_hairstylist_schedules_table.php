<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameColumnClockToTimeInHairstylistSchedulesTable extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_schedule_dates', function (Blueprint $table) {
            $table->renameColumn('clock_in', 'time_start');
            $table->renameColumn('clock_out', 'time_end');
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
            $table->renameColumn('time_start', 'clock_in');
            $table->renameColumn('time_end', 'clock_out');
        });
    }
}
