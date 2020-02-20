<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FraudDetectionLogReferralUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_detection_log_referral_users', function (Blueprint $table) {
            $table->increments('id_fraud_detection_log_referral_users');
            $table->unsignedInteger('id_user');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('referral_code')->nullable();
            $table->dateTime('referral_code_use_date')->nullable();
            $table->integer('referral_code_use_date_count')->nullable();
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
        Schema::dropIfExists('fraud_detection_log_referral_users');
    }
}
