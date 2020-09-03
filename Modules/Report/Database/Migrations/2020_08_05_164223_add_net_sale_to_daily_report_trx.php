<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNetSaleToDailyReportTrx extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx', function (Blueprint $table) {
            $table->integer('trx_shipment_go_send')->default(0)->after('trx_grand');
            $table->integer('trx_net_sale')->default(0)->after('trx_grand');
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
            $table->dropColumn('trx_shipment_go_send');
            $table->dropColumn('trx_net_sale');
        });
    }
}
