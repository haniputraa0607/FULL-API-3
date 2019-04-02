<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalDailyReportTrxMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_daily_report_trx_menu', function (Blueprint $table) {
            $table->increments('id_global_monthly_report_trx_menu');
            $table->date('trx_date')->nullable();
            $table->integer('id_product')->nullable();
            $table->integer('total_rec')->nullable();
            $table->integer('total_qty')->nullable();
            $table->integer('total_nominal')->nullable();
			$table->integer('cust_male')->nullable();
            $table->integer('cust_female')->nullable();
            $table->integer('cust_android')->nullable();
            $table->integer('cust_ios')->nullable();
            $table->integer('cust_telkomsel')->nullable();
            $table->integer('cust_xl')->nullable();
            $table->integer('cust_indosat')->nullable();
            $table->integer('cust_tri')->nullable();
            $table->integer('cust_axis')->nullable();
            $table->integer('cust_smart')->nullable();
            $table->integer('cust_teens')->nullable();
            $table->integer('cust_young_adult')->nullable();
            $table->integer('cust_adult')->nullable();
            $table->integer('cust_old')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('global_daily_report_trx_menu');
    }
}
