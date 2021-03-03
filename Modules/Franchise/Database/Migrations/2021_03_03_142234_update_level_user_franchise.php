<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateLevelUserFranchise extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `user_franchises` CHANGE COLUMN `level` `level` ENUM('Super Admin', 'User Franchise') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `user_franchises` CHANGE COLUMN `level` `user_franchise_status` ENUM('Super Admin', 'Admin') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
