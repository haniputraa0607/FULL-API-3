<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlingOutletGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling_outlet_group', function (Blueprint $table) {
            $table->bigIncrements('id_bundling_outlet_group');
            $table->unsignedInteger('id_bundling');
            $table->unsignedInteger('id_outlet_group');
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
        Schema::dropIfExists('bundling_outlet_group');
    }
}
