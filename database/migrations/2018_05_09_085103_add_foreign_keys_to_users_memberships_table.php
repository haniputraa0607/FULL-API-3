<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUsersMembershipsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users_memberships', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_log_levels_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_membership', 'fk_users_memberships_memberships')->references('id_membership')->on('memberships')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users_memberships', function(Blueprint $table)
		{
			$table->dropForeign('fk_log_levels_users');
			$table->dropForeign('fk_users_memberships_memberships');
		});
	}

}
