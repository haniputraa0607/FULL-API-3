<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubtotalFinalToTransactionProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->integer('transaction_product_subtotal_final')->nullable()->after('transaction_product_subtotal');
            $table->integer('transaction_product_discount_all')->nullable()->after('transaction_product_discount');
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
            $table->dropColumn('transaction_product_subtotal_final');
            $table->dropColumn('transaction_product_discount_all');
        });
    }
}
