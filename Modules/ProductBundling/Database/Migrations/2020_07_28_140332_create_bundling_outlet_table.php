<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlingOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling_outlet', function (Blueprint $table) {
            $table->bigIncrements('id_bundling_outlet');
            $table->unsignedInteger('id_bundling');
            $table->unsignedInteger('id_outlet');
            $table->timestamps();

        });
        Schema::disableForeignKeyConstraints();
        Schema::table('bundling_outlet', function($table) {
            $table->foreign('id_bundling')->references('id_bundling')->on('bundling')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('bundling_outlet');
    }
}
