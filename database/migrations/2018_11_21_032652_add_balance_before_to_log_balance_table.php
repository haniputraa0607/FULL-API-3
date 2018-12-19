<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBalanceBeforeToLogBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_balances', function (Blueprint $table) {
            $table->integer('balance_before')->nullable()->after('balance');
            $table->integer('balance_after')->nullable()->after('balance_before');
            $table->text('enc')->nullable()->after('membership_cashback_percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_balances', function (Blueprint $table) {
            $table->dropColumn('balance_before');
            $table->dropColumn('balance_after');
            $table->dropColumn('enc');
        });
    }
}