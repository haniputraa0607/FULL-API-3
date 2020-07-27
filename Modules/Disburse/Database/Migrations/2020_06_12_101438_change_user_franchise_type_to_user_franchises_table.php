<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUserFranchiseTypeToUserFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `user_franchises` CHANGE COLUMN `user_franchise_type` `user_franchise_type` ENUM('Franchise', 'Not Franchise') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `user_franchises` CHANGE COLUMN `user_franchise_type` `user_franchise_type` ENUM('Franchise', 'Non Franchise') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
