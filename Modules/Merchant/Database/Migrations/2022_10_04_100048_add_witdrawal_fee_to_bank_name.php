<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWitdrawalFeeToBankName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_name', function (Blueprint $table) {
            $table->string('withdrawal_fee_formula')->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_name', function (Blueprint $table) {
            $table->dropColumn('withdrawal_fee_formula');
        });
    }
}
