<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateStartDateEndToPromotionSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotion_schedules', function (Blueprint $table) {
        	$table->dateTime('date_start')->after('schedule_everyday')->nullable()->index();
			$table->dateTime('date_end')->after('date_start')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotion_schedules', function (Blueprint $table) {
        	$table->dropColumn('date_start');
            $table->dropColumn('date_end');
        });
    }
}
