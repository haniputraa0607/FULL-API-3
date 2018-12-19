<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSettingsTable extends Migration {

	public function up()
	{
		Schema::create('settings', function(Blueprint $table)
		{
			$table->increments('id_setting');
			$table->string('key', 200);
			$table->string('value', 200)->nullable();
			$table->text('value_text', 16777215)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('settings');
	}

}
