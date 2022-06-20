<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_schedules', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_schedule');
            $table->unsignedBigInteger('id_user_hair_stylist')->index();
            $table->unsignedInteger('id_outlet')->index();
            $table->unsignedInteger('approve_by')->nullable();
            $table->datetime('request_at')->nullable();
            $table->datetime('approve_at')->nullable();
            $table->datetime('reject_at')->nullable();

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
        Schema::dropIfExists('hairstylist_schedules');
    }
}
