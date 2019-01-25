<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRequestAndResponseLongTextLogActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql2')->statement('ALTER TABLE `log_activities` CHANGE `request` `request` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
        DB::connection('mysql2')->statement('ALTER TABLE `log_activities` CHANGE `response` `response` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
    }

    public function down()
    {
        DB::connection('mysql2')->statement('ALTER TABLE `log_activities` CHANGE `request` `request` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
        DB::connection('mysql2')->statement('ALTER TABLE `log_activities` CHANGE  `response` `response` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
    }
}
