<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToGlobalMonthlyReportTrxMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('global_monthly_report_trx_menu', function (Blueprint $table) {
        	$table->index('id_product');
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
        Schema::table('global_monthly_report_trx_menu', function (Blueprint $table) {
        	$table->dropIndex(['id_product']);
        	$table->dropIndex(['trx_year']);
        	$table->dropIndex(['trx_month']);
        });
    }
}
