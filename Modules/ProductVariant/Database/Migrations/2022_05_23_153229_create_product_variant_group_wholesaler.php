<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantGroupWholesaler extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant_group_wholesalers', function (Blueprint $table) {
            $table->bigIncrements('id_product_variant_group_wholesaler');
            $table->unsignedInteger('id_product_variant_group');
            $table->integer('variant_wholesaler_minimum');
            $table->decimal('variant_wholesaler_unit_price', 30,2)->default(0);
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
        Schema::dropIfExists('product_variant_group_wholesalers');
    }
}
