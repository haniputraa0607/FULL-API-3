<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAutocrmPushLogsTable extends Migration {

	public function up()
	{
		Schema::table('autocrm_push_logs', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_autocrm_push_logs_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('autocrm_push_logs', function(Blueprint $table)
		{
			$table->dropForeign('fk_autocrm_push_logs_users');
		});
	}

}
