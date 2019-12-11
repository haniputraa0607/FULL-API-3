<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProductModifierStockStatusOnProductModifierPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement("ALTER TABLE `product_modifier_prices` CHANGE COLUMN `product_modifier_stock_status` `product_modifier_stock_status` ENUM('Available', 'Sold Out') COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT 'Available' ;");
        DB::connection('mysql')->statement("ALTER TABLE `product_modifier_prices` CHANGE COLUMN `product_modifier_price` `product_modifier_price` DECIMAL(8,2) UNSIGNED NULL");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement("ALTER TABLE `product_modifier_prices` CHANGE COLUMN `product_modifier_stock_status` `product_modifier_stock_status` ENUM('Availvable', 'Sold Out') COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT 'Availvable' ;");
        DB::connection('mysql')->statement("ALTER TABLE `product_modifier_prices` CHANGE COLUMN `product_modifier_price` `product_modifier_price` DECIMAL(8,2) UNSIGNED NOT NULL");
    }
}
