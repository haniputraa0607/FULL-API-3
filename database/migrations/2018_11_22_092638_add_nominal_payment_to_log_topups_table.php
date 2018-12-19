<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNominalPaymentToLogTopupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->integer('nominal_bayar')->nullable()->after('balance_before');
        });

        \DB::statement('UPDATE log_topups SET nominal_bayar = topup_value');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->dropColumn('nominal_bayar');
        });
    }
}
