<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDailyReportMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_report_trx_menu', function (Blueprint $table) {
            $table->increments('id_report_trx_menu');
            $table->date('trx_date')->nullable();
            $table->integer('id_outlet')->nullable();
            $table->integer('id_product')->nullable();
            $table->integer('total_rec')->nullable();
            $table->integer('total_qty')->nullable();
            $table->integer('total_nominal')->nullable();
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
        Schema::dropIfExists('daily_report_trx_menu');
    }
}
