<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairStylistGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_groups', function (Blueprint $table) {
            $table->increments('id_hairstylist_group');
            $table->string('hair_stylist_group_name',155);
            $table->string('hair_stylist_group_code', 155);
            $table->text('hair_stylist_group_description');
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
        Schema::dropIfExists('hairstylist_groups');
    }
}
