<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUserFeaturesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_features', function(Blueprint $table)
		{
			$table->foreign('id_feature', 'fk_user_features_features')->references('id_feature')->on('features')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_user', 'fk_user_features_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_features', function(Blueprint $table)
		{
			$table->dropForeign('fk_user_features_features');
			$table->dropForeign('fk_user_features_users');
		});
	}

}
