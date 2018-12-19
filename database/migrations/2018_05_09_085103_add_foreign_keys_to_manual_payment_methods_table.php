<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToManualPaymentMethodsTable extends Migration {

	public function up()
	{
		Schema::table('manual_payment_methods', function(Blueprint $table)
		{
			$table->foreign('id_manual_payment', 'fk_manual_payment_methods_manual_payments')->references('id_manual_payment')->on('manual_payments')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('manual_payment_methods', function(Blueprint $table)
		{
			$table->dropForeign('fk_manual_payment_methods_manual_payments');
		});
	}

}
