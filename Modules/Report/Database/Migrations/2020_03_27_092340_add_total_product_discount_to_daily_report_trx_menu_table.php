<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalProductDiscountToDailyReportTrxMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx_menu', function (Blueprint $table) {
        	$table->integer('total_product_discount')->nullable()->default(null)->after('total_nominal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_report_trx_menu', function (Blueprint $table) {
        	$table->dropColumn('total_product_discount');
        });
    }
}
