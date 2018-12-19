<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProvincesTable extends Migration {

	public function up()
	{
		Schema::create('provinces', function(Blueprint $table)
		{
			$table->increments('id_province');
			$table->string('province_name', 100);
		});
	}

	public function down()
	{
		Schema::drop('provinces');
	}

}
