<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_groups', function (Blueprint $table) {
            $table->increments('id_transaction_group');
            $table->unsignedInteger('id_user');
            $table->string('transaction_receipt_number')->nullable();
            $table->integer('transaction_subtotal')->default(0);
            $table->integer('transaction_shipment')->default(0);
            $table->integer('transaction_grandtotal')->default(0);
            $table->enum('transaction_payment_status', ['Pending', 'Paid', 'Completed', 'Cancelled'])->default('Pending');
            $table->enum('transaction_payment_type', ['Manual', 'Midtrans', 'Offline', 'Balance', 'Ovo', 'Cimb', 'Ipay88', 'Shopeepay'])->nullable();
            $table->dateTime('transaction_void_date')->nullable();
            $table->dateTime('transaction_transaction_date')->nullable();
            $table->dateTime('transaction_completed_at')->nullable();
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
        Schema::dropIfExists('transaction_groups');
    }
}
