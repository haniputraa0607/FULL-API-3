<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletToDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->integer('id_outlet')->nullable()->after('id_disburse');
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
            $table->drop('id_outlet');
        });
    }
}
