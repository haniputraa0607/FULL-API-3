<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserDevicesTable extends Migration {

	public function up()
	{
		Schema::create('user_devices', function(Blueprint $table)
		{
			$table->increments('id_device_user');
			$table->integer('id_user')->unsigned()->index('fk_user_devices_users')->nullable();
			$table->enum('device_type', array('Android','IOS'))->nullable();
			$table->string('device_id', 20);
			$table->string('device_token', 160);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('user_devices');
	}

}
