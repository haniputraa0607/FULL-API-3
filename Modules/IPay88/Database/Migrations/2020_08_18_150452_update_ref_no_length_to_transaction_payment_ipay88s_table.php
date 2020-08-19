<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateRefNoLengthToTransactionPaymentIpay88sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->string('ref_no')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_ipay88s', function (Blueprint $table) {
            $table->string('ref_no',20)->change();
        });
    }
}
