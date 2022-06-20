<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnOtpToUserHairStylist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->integer('otp_increment')->default(0)->after('balance');
            $table->dateTime('otp_available_time_request')->nullable()->after('balance');
            $table->dateTime('otp_valid_time')->nullable()->after('balance');
            $table->enum('otp_request_status', ['Can Request', 'Can Not Request'])->default('Can Request')->after('balance');
            $table->string('otp_forgot', 255)->nullable()->after('balance');
            $table->integer('sms_increment')->default(0)->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->dropColumn('otp_increment');
            $table->dropColumn('otp_available_time_request');
            $table->dropColumn('otp_valid_time');
            $table->dropColumn('otp_request_status');
            $table->dropColumn('otp_forgot');
            $table->dropColumn('sms_increment');
        });
    }
}
