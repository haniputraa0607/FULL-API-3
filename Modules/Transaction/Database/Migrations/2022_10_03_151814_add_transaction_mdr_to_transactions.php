<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionMdrToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_mdr_charged', ['merchant','outlet'])->nullable()->after('transaction_tax');
            $table->decimal('transaction_mdr', 30, 2)->default(0)->nullable()->after('transaction_tax');
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
            $table->dropColumn('transaction_mdr_charged');
            $table->dropColumn('transaction_mdr');
        });
    }
}
