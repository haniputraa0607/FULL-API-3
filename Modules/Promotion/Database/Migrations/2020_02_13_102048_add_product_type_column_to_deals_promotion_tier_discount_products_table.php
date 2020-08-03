<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductTypeColumnToDealsPromotionTierDiscountProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_tier_discount_products', function (Blueprint $table) {
        	$table->enum('product_type', ['single', 'group'])->after('id_deals');
        	$table->dropForeign('fk_deals_promotion_tier_discount_products_product');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_promotion_tier_discount_products', function (Blueprint $table) {
        	$table->dropColumn('product_type');
        	$table->foreign('id_product', 'fk_deals_promotion_tier_discount_products_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE')->nullable();
        });
    }
}
