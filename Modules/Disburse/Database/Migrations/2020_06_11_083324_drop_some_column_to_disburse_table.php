<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropSomeColumnToDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->dropColumn('id_outlet');
            $table->dropColumn('total_income_central');
            $table->dropColumn('total_expense_central');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('disburse', function (Blueprint $table) {
            $table->integer('id_outlet')->nullable()->after('id_disburse');
            $table->integer('total_income_central')->default(0)->after('disburse_nominal');
            $table->integer('total_expense_central')->default(0)->after('disburse_nominal');
        });
    }
}
