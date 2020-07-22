<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDisburseOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet', function (Blueprint $table) {
            $table->decimal('total_subscription', 30, 4)->default(0)->after('total_expense_central');
            $table->decimal('total_point_use_expense', 30, 4)->default(0)->after('total_expense_central');
            $table->decimal('total_payment_charge', 30, 4)->default(0)->after('total_expense_central');
            $table->decimal('total_delivery_price', 30, 4)->default(0)->after('total_expense_central');
            $table->decimal('total_discount', 30, 4)->default(0)->after('total_expense_central');
            $table->decimal('total_omset', 30, 4)->default(0)->after('total_expense_central');
            $table->decimal('total_fee_item', 30, 4)->default(0)->after('total_expense_central');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_outlet', function (Blueprint $table) {
            $table->dropColumn('total_subscription');
            $table->dropColumn('total_point_use_expense');
            $table->dropColumn('total_payment_charge');
            $table->dropColumn('total_delivery_price');
            $table->dropColumn('total_discount');
            $table->dropColumn('total_omset');
            $table->dropColumn('total_fee_item');
        });
    }
}
