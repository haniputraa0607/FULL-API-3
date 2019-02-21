<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalDailyReportTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_daily_report_trx', function (Blueprint $table) {
            $table->increments('id_global_daily_report_trx');
            $table->date('trx_date')->nullable();
            $table->integer('trx_count')->nullable();
            $table->integer('trx_subtotal')->nullable();
            $table->integer('trx_tax')->nullable();
            $table->integer('trx_shipment')->nullable();
            $table->integer('trx_service')->nullable();
            $table->integer('trx_discount')->nullable();
            $table->integer('trx_grand')->nullable();
            $table->integer('trx_cashback_earned')->nullable();
            $table->integer('trx_point_earned')->nullable();
            $table->text('trx_max')->nullable();
            $table->decimal('trx_average', 20, 2)->nullable();
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
        Schema::dropIfExists('global_daily_report_trx');
    }
}
