<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateManualPaymentsTable extends Migration {

	public function up()
	{
		Schema::create('manual_payments', function(Blueprint $table)
		{
			$table->increments('id_manual_payment');
			$table->char('is_virtual_account', 1)->nullable();
			$table->string('manual_payment_name', 50)->nullable();
			$table->string('manual_payment_logo', 200)->nullable();
			$table->string('account_number', 100)->nullable();
			$table->string('account_name', 200)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('manual_payments');
	}

}
