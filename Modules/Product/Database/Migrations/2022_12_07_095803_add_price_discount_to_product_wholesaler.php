<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPriceDiscountToProductWholesaler extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_wholesalers', function (Blueprint $table) {
            $table->integer('wholesaler_unit_price_discount_percent')->nullable()->default(0)->after('product_wholesaler_unit_price');
            $table->integer('wholesaler_unit_price_before_discount')->nullable()->default(0)->after('product_wholesaler_unit_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_wholesalers', function (Blueprint $table) {
            $table->dropColumn('wholesaler_unit_price_discount_percent');
            $table->dropColumn('wholesaler_unit_price_before_discount');
        });
    }
}
