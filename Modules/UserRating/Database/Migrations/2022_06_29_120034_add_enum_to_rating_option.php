<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnumToRatingOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `rating_options` CHANGE COLUMN `rating_target` `rating_target` ENUM('hairstylist','outlet', 'product') NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `rating_options` CHANGE COLUMN `rating_target` `rating_target` ENUM('hairstylist','outlet') NULL");
    }
}
