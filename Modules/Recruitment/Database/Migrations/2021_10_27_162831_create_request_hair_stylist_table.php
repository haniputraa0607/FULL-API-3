<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestHairStylistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_hair_stylists', function (Blueprint $table) {
            $table->increments('id_request_hair_stylist');
            $table->integer('id_outlet')->unsigned();
            $table->integer('number_of_request');
            $table->enum('status', ['Request','Approved','Rejected'])->default('Request');
            $table->string('applicant')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('id_outlet', 'fk_request_hair_stylist_outlet')->references('id_outlet')->on('outlets')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_hair_stylists');
    }
}
