<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFraudSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fraud_settings', function (Blueprint $table) {
            $table->increments('id_fraud_setting');
            $table->string('parameter');
            $table->string('parameter_detail')->nullable();
            $table->char('email_toogle', 1)->default('0');
            $table->char('sms_toogle', 1)->default('0');
            $table->char('whatsapp_toogle', 1)->default('0');
            $table->text('email_recipient')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_content')->nullable();
            $table->text('sms_recipient')->nullable();
            $table->text('sms_content')->nullable();
            $table->text('whatsapp_recipient')->nullable();
            $table->text('whatsapp_content')->nullable();
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
        Schema::dropIfExists('fraud_settings');
    }
}
