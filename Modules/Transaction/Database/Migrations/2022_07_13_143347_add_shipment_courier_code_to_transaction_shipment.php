<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShipmentCourierCodeToTransactionShipment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->string('shipment_courier_code')->nullable()->after('shipment_courier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_shipments', function (Blueprint $table) {
            $table->drop('shipment_courier_code');
        });
    }
}
