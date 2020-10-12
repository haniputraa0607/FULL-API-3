<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttributePlasticUsedInProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('product_type',['product', 'plastic'])->after('product_name');
            $table->integer('product_capacity')->nullable()->after('product_type');
            $table->integer('plastic_used')->nullable()->after('product_capacity');
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
            $table->dropColumn('plastic_used');
            $table->dropColumn('product_capacity');
            $table->dropColumn('product_type');
        });
    }
}
