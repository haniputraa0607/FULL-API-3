<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant_details', function (Blueprint $table) {
            $table->bigIncrements('id_product_variant_detail');
            $table->unsignedInteger('id_outlet');
            $table->unsignedBigInteger('id_product_variant');
            $table->enum('product_variant_stock_status', ['Available', 'Sold Out']);
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_io_pvd')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product_variant', 'fk_ipvg_pvd')->references('id_product_variant')->on('product_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variant_details');
    }
}
