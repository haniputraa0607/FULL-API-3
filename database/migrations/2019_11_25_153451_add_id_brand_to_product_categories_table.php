<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandToProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('brand_product',function(Blueprint $table){
            $table->unsignedInteger('id_product_category')->nullable()->after('id_product');
            $table->foreign('id_product_category', 'fk_brand_product_id_product_categories')->references('id_product_category')->on('product_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('brand_product', function (Blueprint $table) {
            $table->dropForeign('fk_brand_product_id_product_categories');
            $table->dropColumn('id_product_category');
        });
    }
}
