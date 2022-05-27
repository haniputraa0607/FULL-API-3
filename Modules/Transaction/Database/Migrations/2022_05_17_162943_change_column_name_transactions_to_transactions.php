<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnNameTransactionsToTransactions extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('transactions_reject_at', 'transaction_reject_at');
            $table->renameColumn('transactions_reject_reason', 'transaction_reject_reason');
            $table->renameColumn('transactions_maximum_date_delivery', 'transaction_maximum_date_delivery');
            $table->renameColumn('transactions_maximum_date_process', 'transaction_maximum_date_process');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('transaction_reject_at', 'transactions_reject_at');
            $table->renameColumn('transaction_reject_reason', 'transactions_reject_reason');
            $table->renameColumn('transaction_maximum_date_delivery', 'transactions_maximum_date_delivery');
            $table->renameColumn('transaction_maximum_date_process', 'transactions_maximum_date_process');
        });
    }
}
