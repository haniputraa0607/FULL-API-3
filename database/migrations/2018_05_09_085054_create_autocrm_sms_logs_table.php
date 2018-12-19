<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAutocrmSmsLogsTable extends Migration {

	public function up()
	{
		Schema::create('autocrm_sms_logs', function(Blueprint $table)
		{
			$table->increments('id_autocrm_sms_log');
			$table->integer('id_user')->unsigned()->index('fk_autocrm_sms_logs_users');
			$table->string('sms_log_to', 18);
			$table->text('sms_log_content', 65535);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('autocrm_sms_logs');
	}

}
