<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToLogPointsTable extends Migration {

	public function up()
	{
		Schema::table('log_points', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_log_points_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('log_points', function(Blueprint $table)
		{
			$table->dropForeign('fk_log_points_users');
		});
	}

}
