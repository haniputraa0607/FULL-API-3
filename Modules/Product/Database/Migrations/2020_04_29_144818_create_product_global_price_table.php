<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductGlobalPriceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_global_price', function (Blueprint $table) {
            $table->bigIncrements('id_product_global_price');
            $table->integer('id_product')->unsigned()->index('fk_product_global_price_products');
            $table->integer('product_global_price')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_global_price');
    }
}
