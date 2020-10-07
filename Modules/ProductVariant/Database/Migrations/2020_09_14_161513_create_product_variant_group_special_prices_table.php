<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantGroupSpecialPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant_group_special_prices', function (Blueprint $table) {
            $table->bigIncrements('id_product_variant_group_special_price');
            $table->unsignedInteger('id_outlet');
            $table->unsignedBigInteger('id_product_variant_group');
            $table->decimal('product_variant_group_price');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_io_pvgsp')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product_variant_group', 'fk_ipvg_pvgsp')->references('id_product_variant_group')->on('product_variant_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variant_group_special_prices');
    }
}
