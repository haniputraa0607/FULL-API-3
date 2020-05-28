<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMdrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mdr', function (Blueprint $table) {
            $table->bigIncrements('id_mdr');
            $table->string('payment_name', 191)->nullable();
            $table->integer('mdr')->nullable();
            $table->enum('percent_type', ['Percent', 'Nominal'])->nullable();
            $table->enum('charged', ['Outlet', 'Customer'])->nullable();
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
        Schema::dropIfExists('mdr');
    }
}
