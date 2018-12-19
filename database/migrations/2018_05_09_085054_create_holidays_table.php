<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHolidaysTable extends Migration {

	public function up()
	{
		Schema::create('holidays', function(Blueprint $table)
		{
			$table->increments('id_holiday');
			$table->string('holiday_name', 191);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('holidays');
	}

}
