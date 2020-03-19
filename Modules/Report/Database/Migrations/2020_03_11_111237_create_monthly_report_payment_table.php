<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonthlyReportPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_report_payment', function (Blueprint $table) {
            $table->increments('id_monthly_report_payment');
            $table->unsignedInteger('id_outlet');
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
        Schema::dropIfExists('monthly_report_payment');
    }
}
