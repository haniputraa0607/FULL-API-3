<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubdistricts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subdistricts', function (Blueprint $table) {
            $table->bigIncrements('id_subdistrict');
            $table->integer('id_subdistrict_external');
            $table->integer('id_district');
            $table->string('subdistrict_name');
            $table->string('subdistrict_postal_code', 30);
            $table->string('subdistrict_latitude')->nullable();
            $table->string('subdistrict_longitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subdistricts');
    }
}
