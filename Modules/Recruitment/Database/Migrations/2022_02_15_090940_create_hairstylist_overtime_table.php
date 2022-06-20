<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistOvertimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_overtime', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_overtime');
            $table->bigInteger('id_user_hair_stylist')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->integer('approve_by')->unsigned()->nullable();
            $table->integer('request_by')->unsigned();
            $table->dateTime('date')->nullable();
            $table->enum('time', ['before','after'])->nullable();
            $table->time('duration')->nullable();
            $table->dateTime('request_at');
            $table->dateTime('approve_at')->nullable();
            $table->dateTime('reject_at')->nullable();
            $table->timestamps();

            $table->foreign('id_user_hair_stylist')->references('id_user_hair_stylist')->on('user_hair_stylist')->onDelete('cascade');
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('approve_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('request_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_overtime');
    }
}
