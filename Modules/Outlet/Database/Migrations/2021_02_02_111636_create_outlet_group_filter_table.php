<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletGroupFilterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_group_filter_conditions', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_group_filter_condition');
            $table->unsignedInteger('id_outlet_group');
            $table->string('outlet_group_filter_subject');
            $table->string('outlet_group_filter_operator')->nullable();
            $table->string('outlet_group_filter_parameter')->nullable();
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
        Schema::dropIfExists('outlet_group_filter_conditions');
    }
}
