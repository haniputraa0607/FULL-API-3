<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableShifts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->increments('id_shift');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_user_outletapp');
            $table->dateTime('open_time');
            $table->dateTime('close_time')->nullable();
            $table->double('cash_start',8,2);
            $table->double('cash_end',8, 2)->nullable();
            $table->double('cash_difference',8, 2)->nullable();
            $table->timestamps();

            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_user_outletapp')->references('id_user_outletapp')->on('user_outletapps')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
}
