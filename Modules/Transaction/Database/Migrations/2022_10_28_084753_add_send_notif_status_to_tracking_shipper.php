<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSendNotifStatusToTrackingShipper extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shipment_tracking_updates', function (Blueprint $table) {
            $table->smallInteger('send_notification')->nullable()->after('tracking_timezone');
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
            $table->dropColumn('send_notification');
        });
    }
}
