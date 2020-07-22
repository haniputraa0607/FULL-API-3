<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyReportPaymentSubscription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_report_payment_subscription', function (Blueprint $table) {
            $table->bigIncrements('id_daily_report_payment_subscription');
            $table->date('date')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment')->nullable();
            $table->integer('payment_count')->nullable();
            $table->integer('payment_nominal')->nullable();
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
        Schema::dropIfExists('daily_report_payment_subscription');
    }
}
