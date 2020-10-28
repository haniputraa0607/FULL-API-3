<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGroupScopeToProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `product_modifiers` CHANGE COLUMN `modifier_type` `modifier_type` ENUM('Global', 'Global Brand', 'Specific', 'Modifier Group') NOT NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `product_modifiers` CHANGE COLUMN `modifier_type` `modifier_type` ENUM('Global', 'Global Brand', 'Specific') NOT NULL;");
    }
}
