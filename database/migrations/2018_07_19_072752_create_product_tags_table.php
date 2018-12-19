<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_tags', function (Blueprint $table) {
            $table->increments('id_product_tag');
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_tag');
            $table->timestamps();

            $table->foreign('id_product', 'fk_product_tags_products')
            ->references('id_product')->on('products')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('id_tag', 'fk_product_tags_tags')
            ->references('id_tag')->on('tags')
            ->onDelete('cascade')
            ->onUpdate('cascade');

            $table->unique(['id_product', 'id_tag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_tags');
    }
}
