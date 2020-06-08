<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFkIdUserToFraudDevice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fraud_detection_log_device', function(Blueprint $table)
        {
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
        Schema::table('fraud_detection_log_device', function (Blueprint $table) {
            $table->dropForeign('fk_fraud_detection_log_device_users');
        });
    }
}
