<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveForeignKeyCategoryProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('fk_products_product_categories');
            $table->foreign('id_product_category', 'fk_products_product_categories')->references('id_product_category')->on('product_categories')->onUpdate('SET NULL')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('id_product_category', 'fk_products_product_categories')->references('id_product_category')->on('product_categories')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
