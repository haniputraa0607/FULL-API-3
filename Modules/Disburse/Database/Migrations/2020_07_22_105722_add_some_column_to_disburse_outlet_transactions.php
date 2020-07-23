<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDisburseOutletTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->decimal('subscription', 30, 4)->default(0)->after('expense_central');
            $table->decimal('point_use_expense', 30, 4)->default(0)->after('expense_central');
            $table->decimal('payment_charge', 30, 4)->default(0)->after('expense_central');
            $table->decimal('discount', 30, 4)->default(0)->after('expense_central');
            $table->decimal('fee_item', 30, 4)->default(0)->after('expense_central');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_outlet_transactions', function (Blueprint $table) {
            $table->dropColumn('fee_item');
            $table->dropColumn('discount');
            $table->dropColumn('payment_charge');
            $table->dropColumn('point_use_expense');
            $table->dropColumn('subscription');
        });
    }
}
