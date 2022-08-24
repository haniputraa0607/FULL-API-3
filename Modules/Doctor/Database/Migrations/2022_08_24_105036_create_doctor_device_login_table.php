<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDoctorDeviceLoginTable extends Migration {

	public function up()
	{
		Schema::create('doctor_device_login', function(Blueprint $table)
		{
			$table->increments('id_doctor_last_login');
			$table->integer('id_doctor');
			$table->integer('device_id');
			$table->string('last_login')->nullable();
            $table->enum('status', array('Active','Inactive'));
			$table->timestamps();
		});
	}

	public function down() 
	{
		Schema::drop('doctor_device_login');
	}

}
