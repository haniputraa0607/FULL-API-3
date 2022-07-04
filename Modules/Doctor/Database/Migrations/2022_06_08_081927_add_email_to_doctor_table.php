<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountAndLastOfferingToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('doctor_email')->after('password');
            $table->boolean('is_pin_sent')->nullable()->after('doctor_email');
            $table->boolean('birthday')->after('is_pin_sent');
            $table->boolean('gender')->after('birthday');
            $table->boolean('celebrate')->after('gender');
            $table->boolean('province')->after('celebrate');
            $table->boolean('city')->after('province');
            $table->boolean('address')->after('city');
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
            $table->dropColumn('doctor_email');
            $table->dropColumn('is_pin_sent');
            $table->dropColumn('birthday');
            $table->dropColumn('gender');
            $table->dropColumn('celebrate');
            $table->dropColumn('province');
            $table->dropColumn('city');
            $table->dropColumn('address');
        });
    }
}
