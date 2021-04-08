<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDataTypePlastic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `product_capacity` `product_capacity` DECIMAL (30,5) NULL;");
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `plastic_used` `plastic_used` DECIMAL (30,5) NULL;");
        \DB::statement("ALTER TABLE `product_variant_groups` CHANGE COLUMN `product_variant_groups_plastic_used` `product_variant_groups_plastic_used` DECIMAL (30,5) NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `product_capacity` `product_capacity` INT NULL;");
        \DB::statement("ALTER TABLE `products` CHANGE COLUMN `plastic_used` `plastic_used` INT NULL;");
        \DB::statement("ALTER TABLE `product_variant_groups` CHANGE COLUMN `product_variant_groups_plastic_used` `product_variant_groups_plastic_used` INT NULL;");
    }
}
