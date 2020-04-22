<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCancelReasonToTransactionPickupGoSendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('latest_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
}
