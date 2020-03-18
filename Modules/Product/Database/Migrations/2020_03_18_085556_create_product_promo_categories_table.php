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
            $table->unsignedInteger('product_promo_category_order')->default(0);
            $table->string('product_promo_category_name');
            $table->text('product_promo_category_description')->nullable();
            $table->string('product_promo_category_photo')->nullable();
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
