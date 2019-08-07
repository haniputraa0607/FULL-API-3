<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReversalTransactionPaymentOvosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_ovos', function (Blueprint $table) {
            $table->enum('reversal', ['no', 'not yet', 'yes'])->nullable()->after('push_to_pay_at');
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
            $table->dropColumn('reversal');
        });
    }
}
