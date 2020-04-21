<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductProductPromoCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_product_promo_categories', function (Blueprint $table) {
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_product_promo_category');

            $table->foreign('id_product', 'fk_id_product_product_product_promo_categories')
                ->references('id_product')->on('products')
                ->onDelete('cascade');
            $table->foreign('id_product_promo_category', 'fk_id_product_promo_category_product_product_promo_categories')
                ->references('id_product_promo_category')->on('product_promo_categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_product_promo_categories');
    }
}
