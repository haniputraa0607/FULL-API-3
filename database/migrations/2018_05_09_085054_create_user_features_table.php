<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserFeaturesTable extends Migration {

	public function up()
	{
		Schema::create('user_features', function(Blueprint $table)
		{
			$table->integer('id_user')->unsigned()->index('fk_user_features_users');
			$table->integer('id_feature')->unsigned()->index('fk_user_features_features');
			$table->primary(['id_user','id_feature']);
		});
	}

	public function down()
	{
		Schema::drop('user_features');
	}

}
