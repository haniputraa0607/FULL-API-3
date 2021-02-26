<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlasticTypeOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plastic_type_outlet', function (Blueprint $table) {
            $table->bigIncrements('id_plastic_type_outlet');
            $table->unsignedInteger('id_plastic_type');
            $table->unsignedInteger('id_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_type_outlet');
    }
}
