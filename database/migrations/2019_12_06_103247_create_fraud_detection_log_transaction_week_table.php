<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFraudDetectionLogTransactionWeekTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_detection_log_transaction_week', function (Blueprint $table) {
            $table->increments('id_fraud_detection_log_transaction_week');
            $table->unsignedInteger('id_user');
            $table->year('fraud_detection_year')->nullable();
            $table->integer('fraud_detection_week')->nullable();
            $table->unsignedInteger('count_transaction_week')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');

            $table->string('fraud_setting_parameter_detail')->nullable();
            $table->tinyInteger('fraud_setting_forward_admin_status')->default(0);
            $table->tinyInteger('fraud_setting_auto_suspend_status')->default(0);
            $table->string('fraud_setting_auto_suspend_value',100)->nullable();
            $table->integer('fraud_setting_auto_suspend_time_period')->nullable();

            $table->timestamps();

            $table->foreign('id_user', 'fk_fraud_detection_log_transaction_week_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fraud_detection_log_transaction_week');
    }
}
