<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSubtotalFinalToTransactionProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->dropColumn('transaction_product_subtotal_final');
            $table->integer('transaction_product_net')->nullable()->after('transaction_product_subtotal');
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
            $table->integer('transaction_product_subtotal_final')->nullable()->after('transaction_product_subtotal');
            $table->dropColumn('transaction_product_net');
        });
    }
}
