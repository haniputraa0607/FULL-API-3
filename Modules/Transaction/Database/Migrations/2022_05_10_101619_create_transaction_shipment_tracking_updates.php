<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionShipmentTrackingUpdates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_shipment_tracking_updates', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_shipment_tracking_update');
            $table->unsignedInteger('id_transaction');
            $table->string('shipment_order_id', 200)->nullable();
            $table->text('tracking_description')->nullable();
            $table->string('tracking_location', 250)->nullable();
            $table->dateTime('tracking_date_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_shipment_tracking_updates');
    }
}
