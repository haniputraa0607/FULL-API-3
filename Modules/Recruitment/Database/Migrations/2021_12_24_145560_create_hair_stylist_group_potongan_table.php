<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairStylistGroupPotonganTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_potongans', function (Blueprint $table) {
            $table->increments('id_hairstylist_group_potongan')->unsigned();
            $table->integer('id_hairstylist_group')->unsigned();
            $table->foreign('id_hairstylist_group', 'fk_id_hairstylist_group_potongans')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->string('name_potongan');
            $table->integer('price_potongan');
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
        Schema::dropIfExists('hairstylist_group_potongans');
    }
}
