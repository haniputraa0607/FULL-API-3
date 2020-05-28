<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrxTotalItemToGlobalDailyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_daily_report_trx', function (Blueprint $table) {
			$table->integer('trx_total_item')->nullable()->default(null)->after('trx_count');
			$table->time('first_trx_time')->nullable()->default(null)->after('trx_date');
        	$table->time('last_trx_time')->nullable()->default(null)->after('first_trx_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('global_daily_report_trx', function (Blueprint $table) {
        	$table->dropColumn('trx_total_item');
        	$table->dropColumn('first_trx_time');
        	$table->dropColumn('last_trx_time');
        });
    }
}
