<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeSomeColumnToDisburseOutletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `disburse_outlet_transactions` CHANGE COLUMN `income_central` `income_central` DECIMAL(30, 4) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 0");
        \DB::statement("ALTER TABLE `disburse_outlet_transactions` CHANGE COLUMN `income_outlet` `income_outlet` DECIMAL(30, 4) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 0");
        \DB::statement("ALTER TABLE `disburse_outlet_transactions` CHANGE COLUMN `expense_central` `expense_central` DECIMAL(30, 4) COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 0");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `disburse_outlet_transactions` CHANGE COLUMN `income_central` `income_central` INT COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 0");
        \DB::statement("ALTER TABLE `disburse_outlet_transactions` CHANGE COLUMN `income_outlet` `income_outlet` INT COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 0");
        \DB::statement("ALTER TABLE `disburse_outlet_transactions` CHANGE COLUMN `expense_central` `expense_central` INT COLLATE 'utf8mb4_unicode_ci' NULL DEFAULT 0");
    }
}
