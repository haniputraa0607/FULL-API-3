<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransferStatusToHsLogBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_log_balances', function (Blueprint $table) {
            $table->smallInteger('transfer_status')->default(0)->after('enc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_log_balances', function (Blueprint $table) {
            $table->dropColumn('transfer_status');
        });
    }
}
