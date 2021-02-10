<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletGroupFilterOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_group_filter_outlets', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_group_filter_outlet');
            $table->unsignedInteger('id_outlet_group');
            $table->unsignedInteger('id_outlet');
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
        Schema::dropIfExists('outlet_group_filter_outlets');
    }
}
