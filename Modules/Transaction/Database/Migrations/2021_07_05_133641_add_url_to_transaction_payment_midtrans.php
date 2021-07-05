<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUrlToTransactionPaymentMidtrans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_midtrans', function (Blueprint $table) {
            $table->text('token')->nullable()->after('id_transaction');
            $table->text('redirect_url')->nullable()->after('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_midtrans', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('redirect_url');
        });
    }
}
