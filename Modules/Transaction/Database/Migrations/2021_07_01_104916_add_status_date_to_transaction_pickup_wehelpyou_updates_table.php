<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusDateToTransactionPickupWehelpyouUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_wehelpyou_updates', function (Blueprint $table) {
			$table->datetime('date')->after('status_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_wehelpyou_updates', function (Blueprint $table) {
			$table->dropColumn('date');
        });
    }
}
