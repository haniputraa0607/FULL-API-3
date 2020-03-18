<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductGroupProductPromoCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_group_product_promo_categories', function (Blueprint $table) {
            $table->unsignedInteger('id_product_group');
            $table->unsignedInteger('id_product_promo_category');

            $table->foreign('id_product_group', 'fk_id_product_group_pgppc')
                ->references('id_product_group')->on('product_groups')
                ->onDelete('cascade');
            $table->foreign('id_product_promo_category', 'fk_id_product_promo_category_pgppc')
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
        Schema::dropIfExists('product_group_product_promo_categories');
    }
}
