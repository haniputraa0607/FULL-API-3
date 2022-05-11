<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusRejectedToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE transactions CHANGE transaction_status transaction_status ENUM('Unpaid', 'Pending', 'Rejected', 'On Progress', 'On Delivery', 'Completed') NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE transactions CHANGE transaction_status transaction_status ENUM('Unpaid', 'Pending', 'On Progress', 'On Delivery', 'Completed') NULL");
    }
}
