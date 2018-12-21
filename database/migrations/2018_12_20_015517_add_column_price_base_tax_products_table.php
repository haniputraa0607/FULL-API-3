<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnPriceBaseTaxProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->integer('product_price_base')->unsigned()->nullable()->default(null)->after('product_price');
            $table->integer('product_price_tax')->unsigned()->nullable()->default(null)->after('product_price_base');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('product_prices', function (Blueprint $table) {
            $table->dropColumn('product_price_base');
            $table->dropColumn('product_price_tax');
        });
    }
}
