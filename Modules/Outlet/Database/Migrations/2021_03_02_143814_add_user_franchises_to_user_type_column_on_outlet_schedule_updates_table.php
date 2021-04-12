<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserFranchisesToUserTypeColumnOnOutletScheduleUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_schedule_updates', function (Blueprint $table) {
        	DB::statement("ALTER TABLE outlet_schedule_updates CHANGE COLUMN user_type user_type ENUM('users', 'user_outlets', 'user_franchises') ");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_schedule_updates', function (Blueprint $table) {
        	DB::statement("ALTER TABLE outlet_schedule_updates CHANGE COLUMN user_type user_type ENUM('users', 'user_outlets') ");
        });
    }
}
