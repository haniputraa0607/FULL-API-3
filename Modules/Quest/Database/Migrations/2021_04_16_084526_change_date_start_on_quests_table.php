<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDateStartOnQuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `quests` CHANGE COLUMN `date_start` `date_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), CHANGE COLUMN `publish_start` `publish_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP();');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
