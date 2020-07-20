<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalIncomeToDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->integer('total_income_central')->default(0)->after('disburse_nominal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->dropColumn('disburse_nominal');
        });
    }
}
