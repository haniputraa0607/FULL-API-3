<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManualBookingOrderIdToTransactionPickupGoSendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->string('manual_order_no')->nullable()->after('stop_booking_at');
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
            $table->dropColumn('manual_order_no');
        });
    }
}
