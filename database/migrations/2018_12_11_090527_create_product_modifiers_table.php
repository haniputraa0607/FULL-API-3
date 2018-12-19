<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifiers', function (Blueprint $table) {
            $table->increments('id_product_modifier');
            $table->unsignedInteger('id_product');
            $table->string('type',50);
            $table->string('code',25);
            $table->string('text',100)->nullable()->default(null);
            $table->timestamps();

            $table->foreign('id_product', 'fk_product_modifiers_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifiers');
    }
}
