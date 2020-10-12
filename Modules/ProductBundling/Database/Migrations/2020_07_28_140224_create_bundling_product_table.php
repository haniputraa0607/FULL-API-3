<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlingProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling_product', function (Blueprint $table) {
            $table->increments('id_bundling_product');
            $table->unsignedInteger('id_bundling');
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_brand');
            $table->integer('jumlah');
            $table->decimal('discount');
            $table->timestamps();

        });
        Schema::disableForeignKeyConstraints();
        Schema::table('bundling_product', function($table) {
            $table->foreign('id_bundling')->references('id_bundling')->on('bundling')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_product')->references('id_product')->on('product')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('bundling_product');
    }
}
