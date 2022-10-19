<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->dropColumn('send_email_status');
            $table->dropColumn('total_outlet');
            $table->dropColumn('total_income_outlet');
            $table->integer('id_merchant_log_balance')->nullable()->after('id_disburse');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {
            $table->tinyInteger('send_email_status')->default(0)->after('count_retry');
            $table->smallInteger('total_outlet')->after('id_disburse');
            $table->decimal('total_income_outlet', 30, 4)->default(0)->after('disburse_nominal');
            $table->dropColumn('id_merchant_log_balance');
        });
    }
}
