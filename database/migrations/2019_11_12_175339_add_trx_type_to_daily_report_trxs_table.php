<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrxTypeToDailyReportTrxsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx', function (Blueprint $table) {
            $table->enum('trx_type',['Online','Offline Member','Offline Non Member'])->after('id_outlet');
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
            $table->dropColumn('trx_type');
        });
    }
}
