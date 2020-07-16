<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUserOutletapps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_outletapps', function (Blueprint $table) {
            $table->increments('id_user_outletapp');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_brand')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('level', ['kasir', 'kitchen', 'kasir & kitchen', 'supervisor']);
            $table->timestamps();

            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_brand')->references('id_brand')->on('brands')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_outletapps');
    }
}
