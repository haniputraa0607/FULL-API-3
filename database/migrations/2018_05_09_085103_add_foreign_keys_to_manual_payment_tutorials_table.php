<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToManualPaymentTutorialsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('manual_payment_tutorials', function(Blueprint $table)
		{
			$table->foreign('id_manual_payment_method', 'fk_manual_payment_tutorial_methods')->references('id_manual_payment_method')->on('manual_payment_methods')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('manual_payment_tutorials', function(Blueprint $table)
		{
			$table->dropForeign('fk_manual_payment_tutorial_methods');
		});
	}

}
