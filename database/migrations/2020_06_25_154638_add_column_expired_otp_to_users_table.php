<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnExpiredOtpToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('email_verify_request_status', ['Can Request', 'Can Not Request'])->default('Can Request')->after('email_verified_valid_time');
            $table->enum('otp_request_status', ['Can Request', 'Can Not Request'])->default('Can Request')->after('email_unsubscribed');
            $table->dateTime('otp_valid_time')->nullable()->after('email_unsubscribed');
            $table->index('otp_valid_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp_request_status');
            $table->dropColumn('otp_valid_time');
            $table->dropIndex(['otp_valid_time']);
            $table->dropColumn('email_verify_request_status');
        });
    }
}
