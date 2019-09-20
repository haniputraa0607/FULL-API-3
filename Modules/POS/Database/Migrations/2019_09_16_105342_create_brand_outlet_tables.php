<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandOutletTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_outlet', function (Blueprint $table) {
            $table->increments('id_brand_outlet');
            $table->unsignedInteger('id_brand');
            $table->unsignedInteger('id_outlet');
            $table->timestamps();

            $table->foreign('id_brand', 'fk_brand_outlet_brand')->references('id_brand')->on('brands')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_brand_outlet_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brand_outlet');
    }
}
