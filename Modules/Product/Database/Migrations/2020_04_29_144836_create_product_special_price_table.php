<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductSpecialPriceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_special_price', function (Blueprint $table) {
            $table->bigIncrements('id_product_special_price');
            $table->integer('id_product')->unsigned()->index('fk_product_special_price_products');
            $table->integer('id_outlet')->unsigned()->index('fk_product_special_price_outlets');
            $table->decimal('product_special_price',11,2)->unsigned();
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
        Schema::dropIfExists('product_special_price');
    }
}
