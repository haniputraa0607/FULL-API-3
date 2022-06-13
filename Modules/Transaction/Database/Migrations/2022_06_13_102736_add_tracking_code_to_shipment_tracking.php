<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackingCodeToShipmentTracking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shipment_tracking_updates', function (Blueprint $table) {
            $table->string('tracking_code')->nullable()->after('shipment_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_shipment_tracking_updates', function (Blueprint $table) {
            $table->dropColumn('tracking_code');
        });
    }
}
