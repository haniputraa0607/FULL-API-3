<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPickupWehelpyouUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_pickup_wehelpyou_updates', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_pickup_wehelpyou_update');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_transaction_pickup_wehelpyou');
            $table->string('poNo');
            $table->string('status');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction','fk_id_trx_trx_pickup_wehelpyou')->references('id_transaction')->on('transactions')->onDelete('CASCADE');
            $table->foreign('id_transaction_pickup_wehelpyou','fk_id_trx_pickup_why_trx_pickup_why')->references('id_transaction_pickup_wehelpyou')->on('transaction_pickup_wehelpyous')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_pickup_wehelpyou_updates');
    }
}
