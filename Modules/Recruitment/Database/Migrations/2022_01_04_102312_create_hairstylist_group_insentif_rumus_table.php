<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupInsentifRumusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_insentif_rumus', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_insentif_rumus');
            $table->integer('id_hairstylist_group_insentif')->unsigned();
            $table->foreign('id_hairstylist_group_insentif', 'fk_id_hairstylist_group_insentif_rumus')->references('id_hairstylist_group_insentif')->on('hairstylist_group_insentifs')->onDelete('restrict');
            $table->integer('id_hairstylist_group')->unsigned();
            $table->foreign('id_hairstylist_group', 'fk_id_hairstylist_group_insentifs_rumus')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
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
        Schema::dropIfExists('hairstylist_group_insentif_rumus');
    }
}
