<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateManualPaymentMethodsTable extends Migration {

	public function up()
	{
		Schema::create('manual_payment_methods', function(Blueprint $table)
		{
			$table->increments('id_manual_payment_method');
			$table->integer('id_manual_payment')->unsigned()->index('fk_manual_payment_methods_manual_payments');
			$table->string('payment_method_name', 200);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('manual_payment_methods');
	}

}
