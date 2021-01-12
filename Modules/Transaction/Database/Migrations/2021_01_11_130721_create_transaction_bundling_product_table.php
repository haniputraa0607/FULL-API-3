<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionBundlingProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_bundling_products', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_bundling_product');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_bundling');
            $table->unsignedInteger('id_outlet');
            $table->decimal('transaction_bundling_product_base_price', 30,2);
            $table->decimal('transaction_bundling_product_subtotal', 30,2);
            $table->integer('transaction_bundling_product_qty');
            $table->decimal('transaction_bundling_product_total_discount', 30,2);
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
        Schema::dropIfExists('transaction_bundling_products');
    }
}
