<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHairStylistTheory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hair_stylist_theories', function (Blueprint $table) {
            $table->bigIncrements('id_user_hair_stylist_theory');
            $table->unsignedInteger('id_user_hair_stylist_document');
            $table->unsignedInteger('id_theory');
            $table->string('category_title');
            $table->string('theory_title');
            $table->integer('minimum_score');
            $table->integer('score');
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
        Schema::dropIfExists('user_hair_stylist_theories');
    }
}
