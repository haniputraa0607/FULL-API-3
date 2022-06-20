<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistScheduleDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_schedule_dates', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_schedule_date');
            $table->unsignedBigInteger('id_hairstylist_schedule')->index();
            $table->datetime('date');
            $table->enum('shift', ['Morning', 'Evening']);
            $table->enum('request_by', ['Hairstylist', 'Admin']);

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
        Schema::dropIfExists('hairstylist_schedule_dates');
    }
}
