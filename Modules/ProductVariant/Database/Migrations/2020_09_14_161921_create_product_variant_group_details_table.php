<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantGroupDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant_group_details', function (Blueprint $table) {
            $table->bigIncrements('id_product_variant_group_detail');
            $table->unsignedInteger('id_outlet');
            $table->unsignedBigInteger('id_product_variant_group');
            $table->enum('product_variant_group_visibility', ['Visible', 'Hidden'])->default('Hidden')->nullable();
            $table->enum('product_variant_group_status', ['Active', 'Inactive'])->default('Active');
            $table->enum('product_variant_group_stock_status', ['Available', 'Sold Out'])->default('Available');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_io_pvgd')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product_variant_group', 'fk_ipv_pvgd')->references('id_product_variant_group')->on('product_variant_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variant_group_details');
    }
}
