<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCitiesTable extends Migration {

	public function up()
	{
		Schema::create('cities', function(Blueprint $table)
		{
			$table->increments('id_city');
			$table->integer('id_province')->unsigned()->index('fk_cities_provinces');
			$table->string('city_name', 200);
			$table->string('city_type', 200);
			$table->char('city_postal_code', 5);
		});
	}

	public function down()
	{
		Schema::drop('cities');
	}

}
