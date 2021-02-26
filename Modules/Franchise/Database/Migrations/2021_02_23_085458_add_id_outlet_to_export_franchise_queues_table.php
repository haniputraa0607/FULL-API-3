<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletToExportFranchiseQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('export_franchise_queues', function (Blueprint $table) {
        	$table->integer('id_outlet')->nullable()->after('id_user_franchise');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('export_franchise_queues', function (Blueprint $table) {
        	$table->drop('id_outlet');
        });
    }
}
