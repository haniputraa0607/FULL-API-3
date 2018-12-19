<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDailyReportTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_report_trx', function (Blueprint $table) {
            $table->increments('id_report_trx');
            $table->unsignedInteger('id_outlet');
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
        Schema::dropIfExists('daily_report_trx');
    }
}
