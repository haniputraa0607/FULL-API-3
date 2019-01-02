<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameProductSoldOutToProductStockStatus extends Migration
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
        Schema::table('product_prices', function(Blueprint $table)
        {
            $table->dropColumn('product_sold_out');
            $table->enum('product_stock_status', ['Available', 'Sold Out'])->default('Available')->after('product_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_prices', function(Blueprint $table)
        {
            $table->dropColumn('product_stock_status');
            $table->enum('product_sold_out', ['Available', 'Sold Out'])->default('Available')->after('product_status');
        });
    }
}
