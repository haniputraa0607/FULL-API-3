<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandToMonthlyReportTrxMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_report_trx_menu', function (Blueprint $table) {
        	$table->unsignedInteger('id_brand')->nullable()->after('id_outlet');
        	$table->index('id_brand');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monthly_report_trx_menu', function (Blueprint $table) {
        	$table->dropIndex(['id_brand']);
        	$table->dropColumn('id_brand');
        });
    }
}
