<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDailyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_report_trx', function (Blueprint $table) {
            $table->integer('cust_male')->nullable()->default(null)->after('trx_average');
            $table->integer('cust_female')->nullable()->default(null)->after('cust_male');
            $table->integer('cust_android')->nullable()->default(null)->after('cust_female');
            $table->integer('cust_ios')->nullable()->default(null)->after('cust_android');
            $table->integer('cust_telkomsel')->nullable()->default(null)->after('cust_ios');
            $table->integer('cust_xl')->nullable()->default(null)->after('cust_telkomsel');
            $table->integer('cust_indosat')->nullable()->default(null)->after('cust_xl');
            $table->integer('cust_tri')->nullable()->default(null)->after('cust_indosat');
            $table->integer('cust_axis')->nullable()->default(null)->after('cust_tri');
            $table->integer('cust_smart')->nullable()->default(null)->after('cust_axis');
            $table->integer('cust_teens')->nullable()->default(null)->after('cust_smart');
            $table->integer('cust_young_adult')->nullable()->default(null)->after('cust_teens');
            $table->integer('cust_adult')->nullable()->default(null)->after('cust_young_adult');
            $table->integer('cust_old')->nullable()->default(null)->after('cust_adult');
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
            $table->dropColumn('cust_male');
            $table->dropColumn('cust_female');
            $table->dropColumn('cust_android');
            $table->dropColumn('cust_ios');
            $table->dropColumn('cust_telkomsel');
            $table->dropColumn('cust_xl');
            $table->dropColumn('cust_indosat');
            $table->dropColumn('cust_tri');
            $table->dropColumn('cust_axis');
            $table->dropColumn('cust_smart');
            $table->dropColumn('cust_teens');
            $table->dropColumn('cust_young_adult');
            $table->dropColumn('cust_adult');
            $table->dropColumn('cust_old');
        });
    }
}
