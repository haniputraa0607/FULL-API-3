<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateManualPaymentTutorialsTable extends Migration {

	public function up()
	{
		Schema::create('manual_payment_tutorials', function(Blueprint $table)
		{
			$table->increments('id_manual_payment_tutorial');
			$table->integer('id_manual_payment_method')->unsigned()->default(0)->index('fk_manual_payment_tutorial_methods');
			$table->text('payment_tutorial', 65535);
			$table->smallInteger('payment_tutorial_no');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('manual_payment_tutorials');
	}

}
