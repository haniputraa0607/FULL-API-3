<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAutocrmEmailLogsTable extends Migration {

	public function up()
	{
		Schema::table('autocrm_email_logs', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_autocrm_email_logs_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('autocrm_email_logs', function(Blueprint $table)
		{
			$table->dropForeign('fk_autocrm_email_logs_users');
		});
	}

}
