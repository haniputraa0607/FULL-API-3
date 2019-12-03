<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOrderIdOnTransactionShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql')->statement('ALTER TABLE `transaction_shipments` CHANGE COLUMN `order_id` `order_id` VARCHAR(4) NOT NULL ;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement('ALTER TABLE `transaction_shipments` CHANGE COLUMN `order_id` `order_id` INT UNSIGNED NULL');
    }
}
