<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOutletsTable extends Migration {

	public function up()
	{
		Schema::create('outlets', function(Blueprint $table)
		{
			$table->increments('id_outlet');
			$table->string('outlet_code', 10)->nullable();
			$table->string('outlet_name', 200);
			$table->string('outlet_fax', 25)->nullable();
			$table->string('outlet_address')->nullable();
			$table->integer('id_city')->unsigned()->index('fk_outlets_cities');
			$table->string('outlet_postal_code', 6)->nullable();
			$table->string('outlet_phone', 25)->nullable();
			$table->string('outlet_email', 150)->nullable();
			$table->string('outlet_latitude', 20)->nullable();
			$table->string('outlet_longitude', 20)->nullable();
			$table->time('outlet_open_hours')->nullable();
			$table->time('outlet_close_hours')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('outlets');
	}

}
