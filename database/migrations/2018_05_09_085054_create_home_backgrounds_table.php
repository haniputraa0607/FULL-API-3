<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHomeBackgroundsTable extends Migration {

	public function up()
	{
		Schema::create('home_backgrounds', function(Blueprint $table)
		{
			$table->increments('id_home_background');
			$table->string('when', 45);
			$table->string('picture', 191);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('home_backgrounds');
	}

}
