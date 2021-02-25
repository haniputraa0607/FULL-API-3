<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFilterColumnDataTypeToTextOnExportFranchiseQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	\DB::statement('alter table export_franchise_queues modify filter longtext null;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		\DB::statement('alter table export_franchise_queues modify filter VARCHAR(255) null;');
    }
}
