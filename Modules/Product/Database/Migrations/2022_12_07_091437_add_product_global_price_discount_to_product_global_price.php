<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductGlobalPriceDiscountToProductGlobalPrice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_global_price', function (Blueprint $table) {
            $table->integer('global_price_discount_percent')->nullable()->default(0)->after('product_global_price');
            $table->integer('global_price_before_discount')->nullable()->default(0)->after('product_global_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_global_price', function (Blueprint $table) {
            $table->dropColumn('global_price_discount_percent');
            $table->dropColumn('global_price_before_discount');
        });
    }
}
