<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BalanceNotNullUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement('UPDATE `users` SET `balance`=0 where `balance` is NULL;');
        DB::connection('mysql')->statement(' ALTER TABLE `users` CHANGE COLUMN `balance` `balance` INT(11) NOT NULL DEFAULT 0 ;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement('ALTER TABLE `users` CHANGE COLUMN `balance` `balance` INT(11) NULL DEFAULT NULL ;');
    }
}
