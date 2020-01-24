<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFraudBetweenTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_between_transaction', function (Blueprint $table) {
            $table->bigIncrements('id_fraud_between_transaction');
            $table->unsignedInteger('id_fraud_detection_log_transaction_in_between');
            $table->unsignedInteger('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fraud_between_transaction');
    }
}
