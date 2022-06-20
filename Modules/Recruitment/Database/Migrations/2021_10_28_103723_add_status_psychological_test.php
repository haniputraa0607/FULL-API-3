<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusPsychologicalTest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `user_hair_stylist` CHANGE COLUMN `user_hair_stylist_status` `user_hair_stylist_status` ENUM('Candidate', 'Active', 'Inactive', 'Interviewed', 'Technical Tested', 'Training Completed', 'Psychological Tested', 'Rejected') COLLATE 'utf8mb4_unicode_ci' NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `user_hair_stylist` CHANGE COLUMN `user_hair_stylist_status` `user_hair_stylist_status` ENUM('Candidate', 'Active', 'Inactive', 'Interviewed', 'Technical Tested', 'Training Completed', 'Rejected') COLLATE 'utf8mb4_unicode_ci' NULL");
    }
}
