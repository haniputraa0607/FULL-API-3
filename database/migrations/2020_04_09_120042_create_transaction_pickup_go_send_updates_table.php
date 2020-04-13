<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPickupGoSendUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_pickup_go_send_updates', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_pickup_go_send_update');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_transaction_pickup_go_send');
            $table->string('status');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction','fk_id_trx_trx_pickup_go_send')->references('id_transaction')->on('transactions')->onDelete('CASCADE');
            $table->foreign('id_transaction_pickup_go_send','fk_id_trx_pickup_gosend_trx_pickup_go_send')->references('id_transaction_pickup_go_send')->on('transaction_pickup_go_sends')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_pickup_go_send_updates');
    }
}
