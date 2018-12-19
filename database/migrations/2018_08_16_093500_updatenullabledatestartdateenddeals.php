<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Updatenullabledatestartdateenddeals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `deals` CHANGE `deals_start` `deals_start` DATETIME NULL DEFAULT NULL;");
        DB::statement("ALTER TABLE `deals` CHANGE `deals_end` `deals_end` DATETIME NULL DEFAULT NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `deals` CHANGE `deals_start` `deals_start` DATETIME NOT NULL;");
        DB::statement("ALTER TABLE `deals` CHANGE `deals_end` `deals_end` DATETIME NOT NULL;");
    }
}
