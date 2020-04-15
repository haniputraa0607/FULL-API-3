<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnMdrTypeToDisburseTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_transactions', function (Blueprint $table) {
            $table->enum('mdr_type', ['Percent', 'Nominal'])->nullable()->after('mdr_charged');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_transactions', function (Blueprint $table) {
            $table->dropColumn('mdr_type');
        });
    }
}
