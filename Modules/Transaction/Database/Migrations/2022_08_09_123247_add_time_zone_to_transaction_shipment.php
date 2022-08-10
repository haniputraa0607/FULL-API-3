<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimeZoneToTransactionShipment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shipment_tracking_updates', function (Blueprint $table) {
            $table->string('tracking_timezone')->nullable()->after('tracking_date_time');
            $table->string('tracking_date_time_original')->nullable()->after('tracking_date_time');
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
            $table->dropColumn('tracking_timezone');
            $table->dropColumn('tracking_date_time_original');
        });
    }
}
