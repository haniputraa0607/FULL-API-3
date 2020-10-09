<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_variants', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_product_variant');
            $table->unsignedInteger('id_transaction_product');
            $table->unsignedBigInteger('id_product_variant');
            $table->decimal('transaction_product_variant_price');
            $table->timestamps();

            $table->foreign('id_transaction_product')->on('transaction_products')->references('id_transaction_product');
            $table->foreign('id_product_variant')->on('product_variants')->references('id_product_variant');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_product_variants');
    }
}
