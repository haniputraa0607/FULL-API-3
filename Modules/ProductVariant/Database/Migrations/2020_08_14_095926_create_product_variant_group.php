<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant_groups', function (Blueprint $table) {
            $table->bigIncrements('id_product_variant_group');
            $table->bigInteger('id_product');
            $table->string('product_variant_group_code')->unique();
            $table->text('product_variant_group_name');
            $table->enum('product_variant_group_visibility', ['Visible', 'Hidden'])->default('Visible');
            $table->decimal('product_variant_group_price');
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
        Schema::dropIfExists('product_variant_groups');
    }
}
