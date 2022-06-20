<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionProductServiceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_service_logs', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_product_service_log');
            $table->unsignedInteger('id_transaction_product_service');
            $table->enum('action', ['Start', 'Stop', 'Resume', 'Extend', 'Complete']);
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
        Schema::dropIfExists('transaction_product_service_logs');
    }
}
