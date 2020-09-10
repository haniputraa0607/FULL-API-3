<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeShopeepayToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `trasaction_payment_type` `trasaction_payment_type` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb', 'Ipay88', 'Shopeepay') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `trasaction_payment_type` `trasaction_payment_type` ENUM('Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb', 'Ipay88') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
    }
}
