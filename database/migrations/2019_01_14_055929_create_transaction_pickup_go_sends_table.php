<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPickupGoSendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_pickup_go_sends', function (Blueprint $table) {
            $table->increments('transaction_pickup_go_send');
			$table->integer('id_transaction_pickup')->unsigned();
            $table->string('origin_name');
            $table->string('origin_phone');
            $table->text('origin_address');
            $table->string('origin_note')->nullable();
            $table->string('origin_latitude');
            $table->string('origin_longitude');
            $table->string('destination_name');
            $table->string('destination_phone');
            $table->text('destination_address');
            $table->string('destination_note')->nullable();
            $table->string('destination_latitude');
            $table->string('destination_longitude');
			$table->integer('go_send_id')->nullable();
			$table->string('go_send_order_no')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction_pickup', 'fk_transaction_pickup_go_sends_transaction_pickups')->references('id_transaction_pickup')->on('transaction_pickups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_go_sends');
    }
}
