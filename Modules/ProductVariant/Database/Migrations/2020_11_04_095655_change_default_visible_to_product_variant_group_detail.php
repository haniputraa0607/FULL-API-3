<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDefaultVisibleToProductVariantGroupDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `product_variant_group_details` CHANGE COLUMN `product_variant_group_visibility` `product_variant_group_visibility` ENUM('Visible', 'Hidden') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'Visible'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `product_variant_group_details` CHANGE COLUMN `product_variant_group_visibility` `product_variant_group_visibility` ENUM('Visible', 'Hidden') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 'Hidden'");
    }
}
