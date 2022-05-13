<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnMaximumDateToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('transactions_reject_at')->nullable()->after('void_date');
            $table->string('transactions_reject_reason', 250)->nullable()->after('void_date');
            $table->date('transactions_maximum_date_delivery')->nullable()->after('void_date');
            $table->date('transactions_maximum_date_process')->nullable()->after('void_date');
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
            $table->dropColumn('transactions_reject_at');
            $table->dropColumn('transactions_reject_reason');
            $table->dropColumn('transactions_maximum_date_delivery');
            $table->dropColumn('transactions_maximum_date_process');
        });
    }
}
