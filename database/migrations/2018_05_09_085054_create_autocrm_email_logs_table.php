<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAutocrmEmailLogsTable extends Migration {

	public function up()
	{
		Schema::create('autocrm_email_logs', function(Blueprint $table)
		{
			$table->increments('id_autocrm_email_log');
			$table->integer('id_user')->unsigned()->index('fk_autocrm_email_logs_users');
			$table->string('email_log_to');
			$table->string('email_log_subject');
			$table->text('email_log_message', 16777215);
			$table->boolean('email_log_is_read')->nullable();
			$table->boolean('email_log_is_clicked')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('autocrm_email_logs');
	}

}
