<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTextReplacesTable extends Migration {

	public function up()
	{
		Schema::create('text_replaces', function(Blueprint $table)
		{
			$table->increments('id_text_replace');
			$table->string('keyword', 191)->unique('keyword');
			$table->string('reference', 191);
			$table->enum('type', array('String','Alias','Date','DateTime','Currency'));
			$table->string('default_value', 191)->nullable();
			$table->string('custom_rule', 191)->nullable();
			$table->enum('status', array('Activated','Not Activated'))->nullable()->default('Activated');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('text_replaces');
	}

}
