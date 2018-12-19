<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToLogTopupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->string('payment_type')->default('Midtrans')->after('topup_payment_status');
        });

        Schema::table('log_topup_manuals', function (Blueprint $table) {
            $table->dropColumn('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });

        Schema::table('log_topup_manuals', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction')->after('id_log_topup_manual')->default(0);
        });
    }
}
