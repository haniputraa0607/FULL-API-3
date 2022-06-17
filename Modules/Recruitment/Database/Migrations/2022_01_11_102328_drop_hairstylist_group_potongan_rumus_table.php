<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropHairstylistGroupPotonganRumusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('hairstylist_group_potongan_rumus');
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('hairstylist_group_potongan_rumus', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_potongan_rumus');
            $table->integer('id_hairstylist_group_potongan')->unsigned();
            $table->foreign('id_hairstylist_group_potongan', 'fk_id_hairstylist_group_potongan_rumus')->references('id_hairstylist_group_potongan')->on('hairstylist_group_potongans')->onDelete('restrict');
            $table->integer('id_hairstylist_group')->unsigned();
            $table->foreign('id_hairstylist_group', 'fk_id_hairstylist_group_potongans_rumus')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->timestamps();
        });
    }
}
