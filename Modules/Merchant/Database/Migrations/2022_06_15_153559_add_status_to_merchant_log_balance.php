<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToMerchantLogBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchant_log_balances', function (Blueprint $table) {
            $table->enum('merchant_balance_status', ['Pending', 'On Progress', 'Completed'])->nullable()->after('merchant_balance_source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant_log_balances', function (Blueprint $table) {
            $table->dropColumn('merchant_log_balance_status');
        });
    }
}
