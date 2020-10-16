<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusFlagToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `transaction_flag_invalid` `transaction_flag_invalid` ENUM('Pending Invalid', 'Invalid', 'Valid') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `transaction_flag_invalid` `transaction_flag_invalid` ENUM('Invalid', 'Valid') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
