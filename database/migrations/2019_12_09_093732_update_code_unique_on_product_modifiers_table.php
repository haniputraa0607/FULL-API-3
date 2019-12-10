<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCodeUniqueOnProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement('ALTER TABLE `product_modifiers` ADD UNIQUE INDEX `code_UNIQUE` (`code` ASC)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement('ALTER TABLE `product_modifiers` DROP INDEX `code_UNIQUE`');
    }
}
