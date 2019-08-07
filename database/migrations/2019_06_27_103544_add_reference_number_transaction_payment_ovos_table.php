<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceNumberTransactionPaymentOvosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('batch_no');
            $table->datetime('push_to_pay_at')->nullable()->after('id_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->dropColumn('reference_number');
            $table->dropColumn('push_to_pay_at');
        });
    }
}
