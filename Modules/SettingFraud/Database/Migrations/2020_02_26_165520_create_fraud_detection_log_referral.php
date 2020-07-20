<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFraudDetectionLogReferral extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_detection_log_referral', function (Blueprint $table) {
            $table->increments('id_fraud_detection_log_referral');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_transaction');
            $table->text('referral_code')->nullable();
            $table->dateTime('referral_code_use_date')->nullable();
            $table->tinyInteger('execution_status')->default(0);
            $table->tinyInteger('fraud_setting_parameter_detail')->default(0);
            $table->tinyInteger('fraud_setting_parameter_detail_time')->default(0);
            $table->tinyInteger('fraud_setting_auto_suspend_status')->default(0);
            $table->tinyInteger('fraud_setting_forward_admin_status')->default(0);
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
        Schema::dropIfExists('fraud_detection_log_referral');
    }
}
