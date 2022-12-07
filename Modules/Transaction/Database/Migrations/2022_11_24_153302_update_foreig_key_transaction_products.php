<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateForeigKeyTransactionProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->dropForeign('fk_transaction_products_products');
            $table->dropForeign('fk_transaction_products_transactions');

            $table->foreign('id_product', 'fk_transaction_products_products')->references('id_product')->on('products')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('id_transaction', 'fk_transaction_products_transactions')->references('id_transaction')->on('transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
            $table->foreign('id_product', 'fk_transaction_products_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_transaction', 'fk_transaction_products_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
