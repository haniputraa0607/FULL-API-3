<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeProductVisibleInProductPricesTable extends Migration
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
        Schema::table('product_prices', function (Blueprint $table) {
            DB::statement("ALTER TABLE `product_prices` CHANGE `product_visibility` `product_visibility` ENUM('Hidden', 'Visible') NULL default NULL;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_prices', function (Blueprint $table) {
            DB::statement("ALTER TABLE `product_prices` CHANGE `product_visibility` `product_visibility` ENUM('Hidden', 'Visible') NOT NULL default 'Hidden';");
        });
    }
}
