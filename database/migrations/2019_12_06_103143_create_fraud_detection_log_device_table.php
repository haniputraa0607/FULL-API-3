<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFraudDetectionLogDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_detection_log_device', function (Blueprint $table) {
            $table->increments('id_fraud_detection_log_device');
            $table->unsignedInteger('id_user');
            $table->string('device_id',255)->nullable();
            $table->enum('device_type', array('Android','IOS'))->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');

            $table->string('fraud_setting_parameter_detail')->nullable();
            $table->tinyInteger('fraud_setting_forward_admin_status')->default(0);
            $table->tinyInteger('fraud_setting_auto_suspend_status')->default(0);
            $table->string('fraud_setting_auto_suspend_value',100)->nullable();
            $table->integer('fraud_setting_auto_suspend_time_period')->nullable();

            $table->timestamps();

            $table->foreign('id_user', 'fk_fraud_detection_log_device_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fraud_detection_log_device');
    }
}
