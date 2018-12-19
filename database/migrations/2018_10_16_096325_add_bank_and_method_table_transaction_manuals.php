<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankAndMethodTableTransactionManuals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_manuals', function (Blueprint $table) {
			$table->unsignedInteger('id_bank')->nullable()->after('id_transaction');
			$table->unsignedInteger('id_bank_method')->nullable()->after('id_transaction');
			
			$table->foreign('id_bank', 'fk_transaction_payment_manuals_banks')->references('id_bank')->on('banks')->onUpdate('CASCADE')->onDelete('CASCADE');
			
			$table->foreign('id_bank_method', 'fk_transaction_payment_manuals_bank_methods')->references('id_bank_method')->on('bank_methods')->onUpdate('CASCADE')->onDelete('CASCADE');
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
			$table->dropForeign('fk_transaction_payment_manuals_banks');
            $table->dropColumn('id_bank');
			$table->dropForeign('fk_transaction_payment_manuals_bank_methods');
            $table->dropColumn('id_bank_method');
        });
    }
}
