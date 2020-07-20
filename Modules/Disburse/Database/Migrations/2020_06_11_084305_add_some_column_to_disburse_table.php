<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->integer('id_bank_account')->nullable()->after('disburse_nominal');
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
            $table->dropColumn('id_bank_account');
        });
    }
}
