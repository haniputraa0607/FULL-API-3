<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuinosUsersTable extends Migration {

	public function up()
	{
		Schema::create('quinos_users', function(Blueprint $table)
		{
			$table->increments('id_quinos_user');
			$table->string('username')->nullable();
			$table->string('password');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('quinos_users');
	}

}
