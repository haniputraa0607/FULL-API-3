<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLatestStatusToTransactionPickupWehelpyousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->string('latest_status')->nullable()->after('id_transaction_pickup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->dropColumn('latest_status');
        });
    }
}
