<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_detail', function (Blueprint $table) {
            $table->bigIncrements('id_product_detail');
            $table->integer('id_product')->unsigned()->index('fk_product_detail_products');
            $table->integer('id_outlet')->unsigned()->index('fk_product_detail_outlets');
            $table->enum('product_detail_visibility', array('Hidden','Visible'))->default('Hidden');
            $table->enum('product_detail_status', array('Active','Inactive'))->default('Active');
            $table->enum('product_detail_stock_status', ['Available', 'Sold Out'])->default('Available');
            $table->unsignedInteger('max_order')->nullable();
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
        Schema::dropIfExists('product_detail');
    }
}
