<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserOutletsTable extends Migration {

	public function up()
	{
		Schema::create('user_outlets', function(Blueprint $table)
		{
			$table->integer('id_user')->nullable();
			$table->integer('id_outlet')->nullable();
			$table->char('enquiry', 1)->nullable();
			$table->char('pickup_order', 1)->nullable();
			$table->char('delivery', 1)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('user_outlets');
	}

}
