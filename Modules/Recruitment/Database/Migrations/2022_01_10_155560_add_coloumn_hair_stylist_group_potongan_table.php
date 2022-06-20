<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColoumnHairStylistGroupPotonganTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_potongans', function (Blueprint $table) {
            $table->dropColumn('name_potongan')->nullable();
            $table->dropColumn('price_potongan')->nullable();
            $table->integer('id_hairstylist_group_default_potongans');
            $table->integer('value')->nullable();
            $table->text('formula')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('hairstylist_group_potongans', function (Blueprint $table) {
            $table->text('name_potongan')->nullable();
            $table->integer('price_potongan')->nullable();
            $table->dropColumn('id_hairstylist_group_default_potongans');
            $table->dropColumn('value')->nullable();
            $table->dropColumn('formula')->nullable();
        });
    }
}
