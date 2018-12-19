<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAutocrmPushLogsTable extends Migration {

	public function up()
	{
		Schema::create('autocrm_push_logs', function(Blueprint $table)
		{
			$table->increments('id_autocrm_push_log');
			$table->integer('id_user')->unsigned()->index('fk_autocrm_push_logs_users');
			$table->string('push_log_to');
			$table->string('push_log_subject');
			$table->text('push_log_content', 16777215)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('autocrm_push_logs');
	}

}
