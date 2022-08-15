<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDataTypePriceTransactionProducts extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->decimal('transaction_product_price', 30, 2)->change();
            $table->decimal('transaction_product_price_base', 30, 2)->nullable()->change();
            $table->decimal('transaction_product_price_tax', 30, 2)->nullable()->change();
            $table->decimal('transaction_variant_subtotal', 30, 2)->nullable()->change();
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
            $table->decimal('transaction_product_price', 8, 2)->change();
            $table->decimal('transaction_product_price_base', 8, 2)->nullable()->change();
            $table->decimal('transaction_product_price_tax', 8, 2)->nullable()->change();
            $table->decimal('transaction_variant_subtotal', 8, 2)->nullable()->change();
        });
    }
}
