<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToDailyTrxMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx_menu', function (Blueprint $table) {
            $table->enum('type', ['product', 'plastic'])->default('product')->after('id_product');
        });

        Schema::table('global_daily_report_trx_menu', function (Blueprint $table) {
            $table->enum('type', ['product', 'plastic'])->default('product')->after('id_product');
        });

        Schema::table('global_monthly_report_trx_menu', function (Blueprint $table) {
            $table->enum('type', ['product', 'plastic'])->default('product')->after('id_product');
        });

        Schema::table('monthly_report_trx_menu', function (Blueprint $table) {
            $table->enum('type', ['product', 'plastic'])->default('product')->after('id_product');
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
            $table->dropColumn('type');
        });

        Schema::table('global_daily_report_trx_menu', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('global_monthly_report_trx_menu', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('monthly_report_trx_menu', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
