<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGlobalBrandToProductModifiersType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement("ALTER TABLE `product_modifiers` CHANGE COLUMN `modifier_type` `modifier_type` ENUM('Global', 'Global Brand', 'Specific') COLLATE 'utf8mb4_unicode_ci' NOT NULL ;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement("ALTER TABLE `product_modifiers` CHANGE COLUMN `modifier_type` `modifier_type` ENUM('Global', 'Specific') COLLATE 'utf8mb4_unicode_ci' NOT NULL ;");
    }
}
