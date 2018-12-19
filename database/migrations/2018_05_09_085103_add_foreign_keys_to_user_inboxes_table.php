<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUserInboxesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_inboxes', function(Blueprint $table)
		{
			$table->foreign('id_campaign', 'fk_user_inboxes_campaigns')->references('id_campaign')->on('campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_user', 'fk_user_inboxes_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_inboxes', function(Blueprint $table)
		{
			$table->dropForeign('fk_user_inboxes_campaigns');
			$table->dropForeign('fk_user_inboxes_users');
		});
	}

}
