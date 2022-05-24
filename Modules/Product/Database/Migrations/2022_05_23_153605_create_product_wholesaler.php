<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductWholesaler extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_wholesalers', function (Blueprint $table) {
            $table->bigIncrements('id_product_wholesaler');
            $table->unsignedInteger('id_product');
            $table->integer('product_wholesaler_minimum');
            $table->decimal('product_wholesaler_unit_price', 30,2)->default(0);
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
        Schema::dropIfExists('product_wholesalers');
    }
}
