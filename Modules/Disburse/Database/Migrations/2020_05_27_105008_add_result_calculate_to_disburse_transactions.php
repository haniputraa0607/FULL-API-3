<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResultCalculateToDisburseTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_transactions', function (Blueprint $table) {
            $table->integer('income_outlet')->nullable()->default(0)->after('id_transaction');
            $table->integer('income_central')->nullable()->default(0)->after('id_transaction');
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
            $table->dropColumn('income_outlet');
            $table->dropColumn('income_central');
        });
    }
}
