<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrxTotalItemToGlobalMonthlyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_monthly_report_trx', function (Blueprint $table) {
			$table->integer('trx_total_item')->nullable()->default(null)->after('trx_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('global_monthly_report_trx', function (Blueprint $table) {
        	$table->dropColumn('trx_total_item');
        });
    }
}
