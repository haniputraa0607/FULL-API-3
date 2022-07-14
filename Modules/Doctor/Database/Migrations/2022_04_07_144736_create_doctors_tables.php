<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoctorsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->bigIncrements('id_doctor');
            $table->string('doctor_name');
            $table->string('doctor_phone');
            $table->string('password');
            $table->integer('id_doctor_clinic');
            $table->string('doctor_status');
            $table->string('doctor_session_price');
            $table->boolean('is_active');
            $table->string('doctor_service');
            $table->string('doctor_photo');

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
        Schema::dropIfExists('doctors');
    }
}
