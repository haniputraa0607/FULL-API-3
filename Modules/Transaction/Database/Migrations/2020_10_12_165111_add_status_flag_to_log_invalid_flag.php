<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusFlagToLogInvalidFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `log_invalid_transactions` CHANGE COLUMN `tansaction_flag` `tansaction_flag` ENUM('Pending Invalid', 'Invalid', 'Valid') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `log_invalid_transactions` CHANGE COLUMN `tansaction_flag` `tansaction_flag` ENUM('Invalid', 'Valid') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
