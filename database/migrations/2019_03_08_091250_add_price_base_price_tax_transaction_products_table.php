<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPriceBasePriceTaxTransactionProductsTable extends Migration
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
            $table->decimal('transaction_product_price', 8,2)->change();
            $table->decimal('transaction_product_price_base', 8,2)->nullable()->after('transaction_product_price');
            $table->decimal('transaction_product_price_tax', 8,2)->nullable()->after('transaction_product_price_base');
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
            $table->unsignedInteger('transaction_product_price')->change();
            $table->dropColumn('transaction_product_price_base');
            $table->dropColumn('transaction_product_price_tax');
        });
    }
}
