<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductPromoCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_promo_categories', function (Blueprint $table) {
            $table->increments('id_product_promo_category');
            $table->unsignedInteger('id_product_promo_order');
            $table->string('id_product_promo_name');
            $table->text('id_product_promo_description');
            $table->string('id_product_promo_photo');
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
        Schema::dropIfExists('product_promo_categories');
    }
}
