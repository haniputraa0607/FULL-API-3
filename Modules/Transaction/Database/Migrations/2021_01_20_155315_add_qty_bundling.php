<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQtyBundling extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->integer('transaction_product_bundling_qty')->default(0)->after('transaction_product_qty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->dropColumn('transaction_product_bundling_qty');
        });
    }
}
