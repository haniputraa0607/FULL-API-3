<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `disburse` CHANGE COLUMN `disburse_status` `disburse_status` ENUM('Success', 'Queued', 'Processed', 'Fail', 'Rejected', 'Hold', 'Approved', 'Retry From Failed') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE disburse CHANGE recipient_name beneficiary_name varchar (191)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `disburse` CHANGE COLUMN `disburse_status` `disburse_status` ENUM('Success', 'Fail') COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE disburse CHANGE beneficiary_name recipient_name  varchar (191)");
    }
}
