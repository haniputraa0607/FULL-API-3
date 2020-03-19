<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalDailyReportPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_daily_report_payment', function (Blueprint $table) {
            $table->increments('id_global_daily_report_payment');
            $table->date('trx_date')->nullable();
            $table->string('trx_payment')->nullable();
            $table->integer('trx_payment_count')->nullable();
            $table->integer('trx_payment_nominal')->nullable();
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
        Schema::dropIfExists('global_daily_report_payment');
    }
}
