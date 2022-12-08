<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPriceDiscountToProductVariantGroupWholesaler extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_variant_group_wholesalers', function (Blueprint $table) {
            $table->integer('variant_wholesaler_unit_price_discount_percent')->nullable()->default(0)->after('variant_wholesaler_unit_price');
            $table->integer('variant_wholesaler_unit_price_before_discount')->nullable()->default(0)->after('variant_wholesaler_unit_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variant_group_wholesalers', function (Blueprint $table) {
            $table->dropColumn('variant_wholesaler_unit_price_discount_percent');
            $table->dropColumn('variant_wholesaler_unit_price_before_discount');
        });
    }
}
