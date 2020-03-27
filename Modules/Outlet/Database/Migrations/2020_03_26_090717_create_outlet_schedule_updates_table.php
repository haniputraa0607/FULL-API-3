<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletScheduleUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_schedule_updates', function (Blueprint $table) {
            $table->increments('id_outlet_schedule_update');
            $table->dateTime('date_time');
            $table->unsignedInteger('id_user')->nullable();
            $table->unsignedInteger('id_outlet_app_otp')->nullable();
            $table->enum('user_type',['users','user_outlets'])->nullable();
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_outlet_schedule');
            $table->text('old_data')->nullable();
            $table->text('new_data')->nullable();
            $table->timestamps();

            $table->foreign('id_outlet_app_otp')->references('id_outlet_app_otp')->on('outlet_app_otps')->onDelete('set null');
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_outlet_schedule')->references('id_outlet_schedule')->on('outlet_schedules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_schedule_updates');
    }
}
