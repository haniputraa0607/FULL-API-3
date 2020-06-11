<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ColumnNameTableDisburseTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE disburse_outlet_transactions CHANGE id_disburse id_disburse_outlet INTEGER");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE disburse_outlet_transactions CHANGE id_disburse_outlet id_disburse  INTEGER");
    }
}
