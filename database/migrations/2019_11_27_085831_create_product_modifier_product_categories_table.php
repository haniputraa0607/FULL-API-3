<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_product_categories', function (Blueprint $table) {
            $table->unsignedInteger('id_product_modifier');
            $table->unsignedInteger('id_product_category');

            $table->foreign('id_product_modifier')->on('product_modifiers')->references('id_product_modifier')->onDelete('cascade');
            $table->foreign('id_product_category')->on('product_categories')->references('id_product_category')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifier_product_categories');
    }
}
