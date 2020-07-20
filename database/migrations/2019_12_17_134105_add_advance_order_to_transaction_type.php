<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdvanceOrderToTransactionType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement("ALTER TABLE `transactions` CHANGE COLUMN `trasaction_type` `trasaction_type` ENUM('Pickup Order', 'Delivery', 'Offline', 'Advance Order') COLLATE 'utf8mb4_unicode_ci' NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement("ALTER TABLE `transactions` CHANGE COLUMN `trasaction_type` `trasaction_type` ENUM('Pickup Order', 'Delivery', 'Offline') COLLATE 'utf8mb4_unicode_ci' NOT NULL");
    }
}
