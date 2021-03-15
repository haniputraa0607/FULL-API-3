<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrxProductModToExportFranchiseQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("
            ALTER TABLE `export_franchise_queues` 
            CHANGE COLUMN `report_type` `report_type` ENUM('Payment', 'Transaction', 'Subscription', 'Deals', 'Report Transaction Product', 'Report Transaction Modifier') NULL DEFAULT NULL ;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("
            ALTER TABLE `export_franchise_queues` 
            CHANGE COLUMN `report_type` `report_type` ENUM('Payment', 'Transaction', 'Subscription', 'Deals') NULL DEFAULT NULL ;
        ");
    }
}
