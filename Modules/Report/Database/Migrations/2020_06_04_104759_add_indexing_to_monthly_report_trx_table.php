<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToMonthlyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_report_trx', function (Blueprint $table) {
        	$table->index('id_outlet');
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
        Schema::table('monthly_report_trx', function (Blueprint $table) {
        	$table->dropIndex(['id_outlet']);
        	$table->dropIndex(['trx_year']);
        	$table->dropIndex(['trx_month']);
        });
    }
}
