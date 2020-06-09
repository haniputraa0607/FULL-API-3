<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpenseCentralToDisburseTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_transactions', function (Blueprint $table) {
            $table->integer('expense_central')->nullable()->default(0)->after('income_outlet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_transactions', function (Blueprint $table) {
            $table->dropColumn('expense_central');
        });
    }
}
