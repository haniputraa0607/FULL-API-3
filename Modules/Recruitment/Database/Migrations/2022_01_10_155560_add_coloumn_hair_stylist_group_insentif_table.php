<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColoumnHairStylistGroupInsentifTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_insentifs', function (Blueprint $table) {
            $table->dropColumn('name_insentif')->nullable();
            $table->dropColumn('price_insentif')->nullable();
            $table->integer('id_hairstylist_group_default_insentifs');
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
         Schema::table('hairstylist_group_insentifs', function (Blueprint $table) {
            $table->text('name_insentif')->nullable();
            $table->integer('price_insentif')->nullable();
            $table->dropColumn('id_hairstylist_group_default_insentifs');
            $table->dropColumn('value')->nullable();
            $table->dropColumn('formula')->nullable();
        });
    }
}
