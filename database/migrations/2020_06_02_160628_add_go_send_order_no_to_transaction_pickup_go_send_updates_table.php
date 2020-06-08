<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGoSendOrderNoToTransactionPickupGoSendUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_go_send_updates', function (Blueprint $table) {
            $table->string('go_send_order_no')->after('id_transaction_pickup_go_send')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_go_send_updates', function (Blueprint $table) {
            $table->dropColumn('go_send_order_no');
        });
    }
}
