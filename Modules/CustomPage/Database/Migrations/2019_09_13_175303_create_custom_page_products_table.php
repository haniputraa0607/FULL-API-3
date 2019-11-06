<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomPageProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_page_products', function (Blueprint $table) {
            $table->unsignedInteger('id_custom_page');
            $table->unsignedInteger('id_product');
            $table->timestamps();

            $table->foreign('id_custom_page', 'fk_custom_page_products_id_custom_page')->references('id_custom_page')->on('custom_pages')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_custom_page_products_id_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_page_products');
    }
}
