<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierGroupInventoryBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_group_inventory_brands', function (Blueprint $table) {
            $table->unsignedInteger('id_product_modifier_group');
            $table->unsignedInteger('id_brand');

            $table->foreign('id_product_modifier_group', 'fk_modifier_group_modifier_group_inventory_brand')->on('product_modifier_groups')->references('id_product_modifier_group');
            $table->foreign('id_brand', 'fk_brand_modifier_group_inventory_brand')->on('brands')->references('id_brand');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifier_group_inventory_brands');
    }
}
