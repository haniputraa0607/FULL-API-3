<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToGlobalDailyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_daily_report_trx', function (Blueprint $table) {
        	$table->index('trx_date');
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
        	$table->dropIndex(['trx_date']);
        });
    }
}
