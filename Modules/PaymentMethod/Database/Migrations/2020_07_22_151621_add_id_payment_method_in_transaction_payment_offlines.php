<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdPaymentMethodInTransactionPaymentOfflines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_offlines', function (Blueprint $table) {
            $table->unsignedInteger('id_payment_method')->after('id_transaction')->nullable();
            $table->foreign('id_payment_method')->references('id_payment_method')->on('payment_methods')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_offlines', function (Blueprint $table) {
            $table->dropColumn('id_payment_method');
        });
    }
}
