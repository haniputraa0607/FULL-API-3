<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumFeeToDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->decimal('total_income_outlet', 30, 4)->default(0)->after('disburse_nominal');
            $table->decimal('disburse_fee', 10, 4)->default(0)->after('disburse_nominal');
            $table->tinyInteger('send_email_status')->default(0)->after('count_retry');
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
            $table->dropColumn('total_income_outlet');
            $table->dropColumn('disburse_fee');
            $table->dropColumn('send_email_status');
        });
    }
}
