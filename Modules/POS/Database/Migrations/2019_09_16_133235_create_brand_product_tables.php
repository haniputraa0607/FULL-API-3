<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandProductTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_product', function (Blueprint $table) {
            $table->increments('id_brand_product');
            $table->unsignedInteger('id_brand');
            $table->unsignedInteger('id_product');
            $table->timestamps();

            $table->foreign('id_brand', 'fk_brand_product_brand')->references('id_brand')->on('brands')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_brand_product_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brand_product');
    }
}
