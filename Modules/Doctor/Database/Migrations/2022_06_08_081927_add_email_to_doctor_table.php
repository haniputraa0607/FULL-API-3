<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmailToDoctorTable extends Migration
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
            $table->boolean('birthday')->after('doctor_email');
            $table->boolean('gender')->after('birthday');
            $table->boolean('celebrate')->after('gender');
            $table->string('address')->after('celebrate');
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
            $table->dropColumn('birthday');
            $table->dropColumn('gender');
            $table->dropColumn('celebrate');
        });
    }
}
