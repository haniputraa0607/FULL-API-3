<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyReportPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_report_payment', function (Blueprint $table) {
            $table->increments('id_daily_report_payment');
            $table->unsignedInteger('id_outlet');
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
        Schema::dropIfExists('daily_report_payment');
    }
}
