<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToProductModifierPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_modifier_prices', function (Blueprint $table) {
            $table->enum('product_modifier_visibility',['Visible','Hidden'])->nullable()->after('product_modifier_price');
            $table->enum('product_modifier_status',['Active','Inactive'])->default('Active')->after('product_modifier_visibility');
            $table->enum('product_modifier_stock_status',['Availvable','Sold Out'])->default('Availvable')->after('product_modifier_status');
        });
        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->enum('product_modifier_visibility',['Visible','Hidden'])->default('Hidden')->after('text');
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
            $table->dropColumn('product_modifier_visibility');
            $table->dropColumn('product_modifier_status');
            $table->dropColumn('product_modifier_stock_status');
        });
        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->dropColumn('product_modifier_visibility');
        });
    }
}
