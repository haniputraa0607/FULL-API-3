<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeIpay88ToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `subscription_users` CHANGE COLUMN `payment_method` `payment_method` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb', 'Ipay88') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `subscription_users` CHANGE COLUMN `payment_method` `payment_method` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
