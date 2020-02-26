<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferralCodeDailyTransactions extends Migration
{
    public $set_schema_table = 'daily_transactions';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->table($this->set_schema_table, function (Blueprint $table) {
            $table->string('referral_code', 199)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql2')->table($this->set_schema_table, function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
}
