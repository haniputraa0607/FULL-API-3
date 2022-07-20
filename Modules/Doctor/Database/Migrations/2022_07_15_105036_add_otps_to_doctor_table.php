<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOtpsToDoctorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //update enum to doctor status
        DB::statement('ALTER TABLE doctors CHANGE doctor_status doctor_status ENUM("Offline", "Online" ,"Busy") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

        //add otps field
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('otp_forgot')->after('doctor_photo');
            $table->enum('otp_request_status', array('Can Request', 'Can Not Request'))->after('otp_forgot');
            $table->timestamp('otp_valid_time')->after('otp_request_status');
            $table->timestamp('otp_available_time_request')->after('otp_valid_time');
            $table->integer('otp_increment')->after('otp_available_time_request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('doctor_status')->change();
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('otp_forgot');
            $table->dropColumn('otp_request_status');
            $table->dropColumn('otp_valid_time');
            $table->dropColumn('otp_available_time_request');
            $table->dropColumn('otp_increment');
        });
    }
}
