<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusMerchantLogBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE merchant_log_balances CHANGE COLUMN merchant_balance_status merchant_balance_status ENUM('Pending','On Progress','Completed', 'Failed') DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE merchant_log_balances CHANGE COLUMN merchant_balance_status merchant_balance_status ENUM('Pending','On Progress','Completed') DEFAULT NULL");
    }
}
