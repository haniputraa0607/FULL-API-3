<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMonthYearLastUpdatedByToHairstylistSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_schedules', function (Blueprint $table) {
            $table->integer('last_updated_by')->after('approve_by')->nullable();
        	$table->tinyInteger('schedule_month')->after('last_updated_by')->nullable();
            $table->year('schedule_year')->after('schedule_month')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_schedules', function (Blueprint $table) {
        	$table->dropColumn('last_updated_by');
        	$table->dropColumn('schedule_month');
        	$table->dropColumn('schedule_year');
        });
    }
}
