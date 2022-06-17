<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletBoxToHairstylistScheduleDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_schedule_dates', function (Blueprint $table) {
        	$table->unsignedInteger('id_outlet_box')->nullable()->after('request_by');

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
        	$table->dropColumn('id_outlet_box');
        });
    }
}
