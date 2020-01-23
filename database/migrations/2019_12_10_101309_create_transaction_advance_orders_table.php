<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAdvanceOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_advance_orders', function (Blueprint $table) {
            $table->increments('id_transaction_advance_order');
            $table->unsignedInteger('id_transaction');
            $table->string('order_id', 4);
            $table->dateTime('receive_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('send_at')->nullable();
            $table->dateTime('send_by_system_at')->nullable();
            $table->dateTime('reject_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->text('address');
            $table->string('receiver_name');
            $table->string('receiver_phone',15);
            $table->dateTime('date_delivery');
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
        Schema::dropIfExists('transaction_advance_orders');
    }
}
