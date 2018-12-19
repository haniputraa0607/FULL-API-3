<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCourierTable extends Migration {

	public function up()
	{
		Schema::create('courier', function(Blueprint $table)
		{
			$table->increments('id_courier');
			$table->string('short_name', 191)->unique();
			$table->string('name', 191);
			$table->enum('status', array('Active','Deactive'))->default('Active');
			$table->enum('courier_type', array('Internal','External'))->default('External');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('courier');
	}

}
