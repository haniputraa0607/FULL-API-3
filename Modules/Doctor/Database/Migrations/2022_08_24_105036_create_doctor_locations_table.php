<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDoctorLocationsTable extends Migration {

	public function up()
	{
		Schema::create('doctor_locations', function(Blueprint $table)
		{
			$table->increments('id_location');
			$table->integer('id_doctor');
			$table->string('action');
			$table->string('lat')->nullable();
            $table->string('lng')->nullable();
			$table->timestamps();
		});
	}

	public function down() 
	{
		Schema::drop('doctor_locations');
	}

}
