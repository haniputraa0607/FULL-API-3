<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHairStylistExperienceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hair_stylist_experiences', function (Blueprint $table) {
            $table->increments('id_user_hair_stylist_experience');
            $table->bigInteger('id_user_hair_stylist')->unsigned();
            $table->text('value');
            $table->timestamps();

            $table->foreign('id_user_hair_stylist', 'fk_user_hair_stylist_experience')->references('id_user_hair_stylist')->on('user_hair_stylist')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_hair_stylist_experiences');
    }
}
