<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGreetingsTable extends Migration {

	public function up()
	{
		Schema::create('greetings', function(Blueprint $table)
		{
			$table->increments('id_greetings');
			$table->string('when', 45);
			$table->string('greeting', 191);
			$table->string('greeting2', 191)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('greetings');
	}

}
