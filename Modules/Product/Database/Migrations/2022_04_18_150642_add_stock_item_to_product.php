<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStockItemToProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_detail', function (Blueprint $table) {
            $table->integer('product_detail_stock_item')->after('product_detail_stock_status')->default(0);
        });

        Schema::table('product_variant_group_details', function (Blueprint $table) {
            $table->integer('product_variant_group_stock_item')->after('product_variant_group_stock_status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_detail', function (Blueprint $table) {
            $table->dropColumn('product_detail_stock_item');
        });

        Schema::table('product_variant_group_details', function (Blueprint $table) {
            $table->dropColumn('product_variant_group_stock_item');
        });
    }
}
