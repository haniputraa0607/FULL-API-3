<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDimensionFieldToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('product_length')->after('plastic_used')->nullable();
            $table->decimal('product_width')->after('plastic_used')->nullable();
            $table->decimal('product_height')->after('plastic_used')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_length');
            $table->dropColumn('product_width');
            $table->dropColumn('product_height');
        });
    }
}
