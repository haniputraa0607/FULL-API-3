<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_details', function (Blueprint $table) {
            $table->increments('id_product_modifier_detail');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_product_modifier');
            $table->enum('product_modifier_visibility',['Visible','Hidden'])->nullable();
            $table->enum('product_modifier_status',['Active','Inactive'])->default('Active');
            $table->enum('product_modifier_stock_status',['Availvable','Sold Out'])->default('Availvable');
            $table->timestamps();

            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product_modifier')->references('id_product_modifier')->on('product_modifiers')->onDelete('cascade');
        });
        Schema::table('product_modifier_prices', function (Blueprint $table) {
            $table->dropColumn('product_modifier_visibility');
            $table->dropColumn('product_modifier_status');
            $table->dropColumn('product_modifier_stock_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_modifier_prices', function (Blueprint $table) {
            $table->enum('product_modifier_visibility',['Visible','Hidden'])->nullable()->after('product_modifier_price');
            $table->enum('product_modifier_status',['Active','Inactive'])->default('Active')->after('product_modifier_visibility');
            $table->enum('product_modifier_stock_status',['Availvable','Sold Out'])->default('Availvable')->after('product_modifier_status');
        });
        Schema::dropIfExists('product_modifier_details');
    }
}
