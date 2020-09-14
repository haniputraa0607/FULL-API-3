<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant', function (Blueprint $table) {
            $table->bigIncrements('product_variant_id');
            $table->string('product_variant_name', 255);
            $table->bigInteger('parent_id')->nullable();
            $table->enum('product_variant_visibility', ['Visible', 'Hidden'])->default('Visible');
            $table->bigInteger('product_variant_order')->nullable();
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
        Schema::dropIfExists('product_variant');
    }
}
