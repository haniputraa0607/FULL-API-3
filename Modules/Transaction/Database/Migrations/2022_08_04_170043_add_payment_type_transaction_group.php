<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeTransactionGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transaction_groups` CHANGE COLUMN `transaction_payment_type` `transaction_payment_type` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb', 'Ipay88', 'Shopeepay', 'Xendit') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transaction_groups` CHANGE COLUMN `transaction_payment_type` `transaction_payment_type` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb', 'Ipay88', 'Shopeepay') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
