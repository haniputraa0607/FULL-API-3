<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOutletHolidaysTable extends Migration {

	public function up()
	{
		Schema::create('outlet_holidays', function(Blueprint $table)
		{
			$table->integer('id_outlet')->unsigned()->index('fk_outlet_holidays_outlets');
			$table->integer('id_holiday')->unsigned()->index('fk_outlet_holidays_holidays');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('outlet_holidays');
	}

}
