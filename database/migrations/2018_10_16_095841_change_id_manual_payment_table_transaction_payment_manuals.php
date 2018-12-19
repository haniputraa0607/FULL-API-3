<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdManualPaymentTableTransactionPaymentManuals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_manuals', function (Blueprint $table) {
			$table->unsignedInteger('id_manual_payment')->nullable()->after('id_transaction');
			
			$table->foreign('id_manual_payment', 'fk_transaction_payment_manuals_manual_payments')->references('id_manual_payment')->on('manual_payments')->onUpdate('CASCADE')->onDelete('CASCADE');
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
            $table->dropColumn('id_manual_payment');
			
			$table->dropForeign('fk_transaction_payment_manuals_manual_payments');
        });
    }
}
