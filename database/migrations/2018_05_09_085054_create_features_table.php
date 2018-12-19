<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFeaturesTable extends Migration {

	public function up()
	{
		Schema::create('features', function(Blueprint $table)
		{
			$table->increments('id_feature');
			$table->enum('feature_type', array('Report','List','Detail','Create','Update','Delete'));
			$table->string('feature_module', 100);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('features');
	}

}
