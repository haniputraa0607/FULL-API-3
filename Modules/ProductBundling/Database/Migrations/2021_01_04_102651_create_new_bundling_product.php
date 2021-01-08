<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewBundlingProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling_product', function (Blueprint $table) {
            $table->increments('id_bundling_product');
            $table->unsignedInteger('id_bundling');
            $table->unsignedInteger('id_brand');
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_product_variant_group')->nullable();
            $table->integer('bundling_product_qty')->default(0);
            $table->enum('bundling_product_discount_type', ['Percent','Nominal'])->nullable();
            $table->decimal('bundling_product_discount', 30, 2)->default(0);
            $table->decimal('charged_central', 5,2)->default(0);
            $table->decimal('charged_outlet', 5,2)->default(0);
            $table->timestamps();
            $table->foreign('id_bundling', 'fk_bundling_product_bundling')->references('id_bundling')->on('bundling')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bundling_product');
    }
}
