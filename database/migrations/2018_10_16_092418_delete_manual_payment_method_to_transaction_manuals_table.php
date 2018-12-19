<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteManualPaymentMethodToTransactionManualsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_manuals', function (Blueprint $table) {
            $table->unsignedInteger('id_manual_payment_method')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_manuals', function (Blueprint $table) {
            $table->unsignedInteger('id_manual_payment_method')->nullable(false)->change();
        });
    }
}
