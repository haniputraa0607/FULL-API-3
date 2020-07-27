<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnRefundWithPointToDailyReportPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_payment', function (Blueprint $table) {
            $table->tinyInteger('refund_with_point')->default(0)->after('id_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_report_payment', function (Blueprint $table) {
            $table->dropColumn('refund_with_point');
        });
    }
}
