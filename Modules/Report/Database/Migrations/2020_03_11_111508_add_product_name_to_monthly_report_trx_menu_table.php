<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductNameToMonthlyReportTrxMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_report_trx_menu', function (Blueprint $table) {
        	$table->string('product_name', 200)->nullable()->default(null)->after('id_product');
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
        	$table->dropColumn('product_name');
        });
    }
}
