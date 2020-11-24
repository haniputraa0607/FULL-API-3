<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierGroupPivotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_group_pivots', function (Blueprint $table) {
            $table->unsignedInteger('id_product')->nullable();
            $table->unsignedBigInteger('id_product_variant')->nullable();
            $table->unsignedInteger('id_product_modifier_group');
            $table->foreign('id_product_modifier_group', 'fk_ipmg_pmgp_pmg')->on('product_modifier_groups')->references('id_product_modifier_group')->onDelete('cascade');
            $table->foreign('id_product', 'fk_ip_pmgp_pm')->references('id_product')->on('products')->onDelete('cascade');
            $table->foreign('id_product_variant', 'fk_ipv_pmgp_pm')->references('id_product_variant')->on('product_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifier_group_pivots');
    }
}
