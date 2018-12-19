<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldToTransactionTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('trasaction_type', ['Pickup Order', 'Delivery', 'Offline'])->after('transaction_receipt_number');
            $table->enum('trasaction_payment_type', ['Manual', 'Midtrans', 'Offline'])->after('transaction_cashback_earned');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('trasaction_type');
            $table->dropColumn('trasaction_payment_type');
        });
    }
}
