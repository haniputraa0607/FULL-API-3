<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPickupByWehelpyouToTransactionPickupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	\DB::statement("ALTER TABLE `transaction_pickups` CHANGE COLUMN `pickup_by` `pickup_by` ENUM('Customer', 'GO-SEND', 'Wehelpyou')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	\DB::statement("ALTER TABLE `transaction_pickups` CHANGE COLUMN `pickup_by` `pickup_by` ENUM('Customer','GO-SEND')");
    }
}
