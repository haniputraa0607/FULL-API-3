<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPickupWehelpyousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_pickup_wehelpyous', function (Blueprint $table) {

        	$table->increments('id_transaction_pickup_wehelpyou');
			$table->integer('id_transaction_pickup')->unsigned();
			$table->string('vehicle_type');
			$table->string('courier');
			$table->boolean('box');
			$table->string('sender_name');
            $table->string('sender_phone');
            $table->text('sender_address');
            $table->string('sender_latitude');
            $table->string('sender_longitude');
            $table->text('sender_notes')->nullable();
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->text('receiver_address');
            $table->text('receiver_notes')->nullable();
            $table->string('receiver_latitude');
            $table->string('receiver_longitude');
            $table->string('item_specification_name');
            $table->text('item_specification_item_description');
            $table->integer('item_specification_length');
            $table->integer('item_specification_width');
            $table->integer('item_specification_height');
            $table->integer('item_specification_weight');
            $table->text('item_specification_remarks');
            $table->string('tracking_driver_name')->nullable();
            $table->string('tracking_driver_phone')->nullable();
            $table->string('tracking_live_tracking_url')->nullable();
            $table->string('tracking_vehicle_number')->nullable();
            $table->string('tracking_receiver_name')->nullable();
            $table->string('tracking_driver_log')->nullable();
            $table->string('poNo')->nullable();
            $table->string('service')->nullable();
            $table->string('price')->nullable();
            $table->string('distance')->nullable();
            $table->integer('order_detail_id')->nullable();
            $table->string('order_detail_po_no')->nullable();
            $table->string('order_detail_awb_no')->nullable();
            $table->string('order_detail_order_date')->nullable();
            $table->string('order_detail_delivery_type_id')->nullable();
            $table->string('order_detail_total_amount')->nullable();
            $table->string('order_detail_partner_id')->nullable();
            $table->string('order_detail_status_id')->nullable();
            $table->string('order_detail_gosend_code')->nullable();
            $table->string('order_detail_speedy_code')->nullable();
            $table->string('order_detail_lalamove_code')->nullable();
            $table->boolean('order_detail_is_multiple')->nullable();
            $table->string('order_detail_createdAt')->nullable();
            $table->string('order_detail_updatedAt')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction_pickup', 'fk_transaction_pickup_wehelpyous_transaction_pickups')->references('id_transaction_pickup')->on('transaction_pickups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_pickup_wehelpyous');
    }
}
