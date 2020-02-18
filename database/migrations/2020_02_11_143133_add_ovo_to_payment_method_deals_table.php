<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOvoToPaymentMethodDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement("ALTER TABLE `deals_users` CHANGE COLUMN `payment_method` `payment_method` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL ;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement("ALTER TABLE `deals_users` CHANGE COLUMN `payment_method` `payment_method` ENUM('Manual', 'Midtrans', 'Offline', 'Balance') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL ;");
    }
}
