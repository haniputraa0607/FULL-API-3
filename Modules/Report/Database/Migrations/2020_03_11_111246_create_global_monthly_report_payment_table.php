<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalMonthlyReportPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_monthly_report_payment', function (Blueprint $table) {
            $table->increments('id_global_monthly_report_payment');
            $table->tinyInteger('trx_month')->nullable();
            $table->year('trx_year')->nullable();
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
        Schema::dropIfExists('global_monthly_report_payment');
    }
}
