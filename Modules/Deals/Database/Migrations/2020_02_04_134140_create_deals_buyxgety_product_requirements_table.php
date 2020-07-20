<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsBuyxgetyProductRequirementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_buyxgety_product_requirements', function (Blueprint $table) {
            $table->increments('id_deals_buyxgety_product');
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_product')->nullable();
            $table->unsignedInteger('id_product_category')->nullable();
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_buyxgety_product_requirements_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_deals_buyxgety_product_requirements_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE')->nullable();
            $table->foreign('id_product_category', 'fk_deals_buyxgety_product_requirements_product_category')->references('id_product_category')->on('product_categories')->onUpdate('CASCADE')->onDelete('CASCADE')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_buyxgety_product_requirements');
    }
}
