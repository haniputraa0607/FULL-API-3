<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManualRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manual_refunds', function (Blueprint $table) {
            $table->bigIncrements('id_manual_refund');
            $table->unsignedInteger('id_transaction');
            $table->dateTime('refund_date');
            $table->text('note')->nullable();
            $table->text('images')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('id_transaction')->references('id_transaction')->on('transactions');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manual_refunds');
    }
}
