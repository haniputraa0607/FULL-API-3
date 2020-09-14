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
        Schema::create('product_variant_group', function (Blueprint $table) {
            $table->bigIncrements('product_variant_group_id');
            $table->bigInteger('product_id');
            $table->string('product_variant_group_code')->unique();
            $table->text('product_variant_group_name');
            $table->enum('product_variant_group_visibility', ['Visible', 'Hidden'])->default('Visible');
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
        Schema::dropIfExists('product_variant_group');
    }
}
