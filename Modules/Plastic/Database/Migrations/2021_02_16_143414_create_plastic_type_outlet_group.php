<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlasticTypeOutletGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plastic_type_outlet_group', function (Blueprint $table) {
            $table->bigIncrements('id_plastic_type_outlet_group');
            $table->unsignedInteger('id_plastic_type');
            $table->unsignedInteger('id_outlet_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_type_outlet_group');
    }
}
