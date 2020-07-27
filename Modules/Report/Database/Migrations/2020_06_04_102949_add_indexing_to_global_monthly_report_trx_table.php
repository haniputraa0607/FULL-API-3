<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToGlobalMonthlyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_monthly_report_trx', function (Blueprint $table) {
        	$table->index('trx_year');
        	$table->index('trx_month');
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
        	$table->dropIndex(['trx_year']);
        	$table->dropIndex(['trx_month']);
        });
    }
}
