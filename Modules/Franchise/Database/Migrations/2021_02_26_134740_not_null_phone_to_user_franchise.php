<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotNullPhoneToUserFranchise extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE user_franchises ADD COLUMN auto_generate_password SMALLINT DEFAULT 0 AFTER password");
        \DB::statement("ALTER TABLE user_franchises ADD COLUMN user_franchise_status ENUM('Active', 'Inactive') DEFAULT 'Active' AFTER email");
        \DB::statement("ALTER TABLE user_franchises CHANGE phone phone varchar (15) NULL UNIQUE");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE user_franchises DROP COLUMN auto_generate_password;");
        \DB::statement("ALTER TABLE user_franchises DROP COLUMN user_franchise_status;");
        \DB::statement("ALTER TABLE user_franchises CHANGE phone phone varchar (15) NOT NULL UNIQUE");
    }
}
