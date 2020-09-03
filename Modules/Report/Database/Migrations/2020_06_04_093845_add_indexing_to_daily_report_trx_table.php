<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToDailyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx', function (Blueprint $table) {
        	$table->index('trx_type');
        	$table->index('trx_date');
        	$table->index('id_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_report_trx', function (Blueprint $table) {
        	$table->dropIndex(['trx_type']);
        	$table->dropIndex(['trx_date']);
        	$table->dropIndex(['id_outlet']);
        });
    }
}
