<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePhoneToUniqueUserHs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update('alter table `user_hair_stylist` modify `phone_number` VARCHAR(191) UNIQUE NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::update('alter table `user_hair_stylist` modify `phone_number` VARCHAR(191) NULL');
    }
}
