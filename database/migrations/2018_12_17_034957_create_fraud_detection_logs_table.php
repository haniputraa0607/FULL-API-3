<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFraudDetectionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_detection_logs', function (Blueprint $table) {
            $table->increments('id_fraud_detection_log');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_fraud_setting');
            $table->unsignedInteger('count_transaction_day')->nullable();
            $table->unsignedInteger('count_transaction_week')->nullable();
            $table->unsignedInteger('id_transaction')->nullable();
            $table->unsignedInteger('id_device_user')->nullable();
            $table->timestamps();

            $table->foreign('id_user', 'fk_fraud_detection_logs_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_fraud_setting', 'fk_fraud_detection_logs_fraud_settings')->references('id_fraud_setting')->on('fraud_settings')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_transaction', 'fk_fraud_detection_logs_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_device_user', 'fk_fraud_detection_logs_user_devices')->references('id_device_user')->on('user_devices')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fraud_detection_logs');
    }
}
