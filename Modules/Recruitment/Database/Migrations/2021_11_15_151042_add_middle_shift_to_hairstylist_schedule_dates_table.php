<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMiddleShiftToHairstylistScheduleDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_schedule_dates', function (Blueprint $table) {
        	\DB::statement("ALTER TABLE `hairstylist_schedule_dates` CHANGE COLUMN `shift` `shift` ENUM('Morning', 'Middle', 'Evening') COLLATE 'utf8mb4_unicode_ci' NULL");
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
        	\DB::statement("ALTER TABLE `hairstylist_schedule_dates` CHANGE COLUMN `shift` `shift` ENUM('Morning', 'Evening') COLLATE 'utf8mb4_unicode_ci' NULL");
        });
    }
}
