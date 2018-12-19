<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAutocrmsTable extends Migration {

	public function up()
	{
		Schema::create('autocrms', function(Blueprint $table)
		{
			$table->increments('id_autocrm');
			$table->enum('autocrm_type', array('Response','Cron'))->default('Cron');
			$table->enum('autocrm_trigger', array('Daily','Weekly','Monthly','Yearly'))->nullable();
			$table->string('autocrm_cron_reference', 50)->nullable();
			$table->string('autocrm_title', 191);
			$table->char('autocrm_email_toogle', 1)->default(0);
			$table->char('autocrm_sms_toogle', 1)->default(0);
			$table->char('autocrm_push_toogle', 1)->default(0);
			$table->char('autocrm_inbox_toogle', 1)->default(0);
			$table->char('autocrm_forward_toogle', 1)->default(0);
			$table->string('autocrm_email_subject')->nullable();
			$table->text('autocrm_email_content', 16777215)->nullable();
			$table->text('autocrm_sms_content', 65535)->nullable();
			$table->text('autocrm_push_subject', 65535)->nullable();
			$table->text('autocrm_push_content', 65535)->nullable();
			$table->string('autocrm_push_image')->nullable();
			$table->string('autocrm_push_clickto', 100)->nullable();
			$table->string('autocrm_push_link')->nullable();
			$table->string('autocrm_push_id_reference')->nullable();
			$table->text('autocrm_inbox_subject', 65535)->nullable();
			$table->text('autocrm_inbox_content', 65535)->nullable();
			$table->text('autocrm_forward_email', 65535)->nullable();
			$table->text('autocrm_forward_email_subject', 65535)->nullable();
			$table->text('autocrm_forward_email_content', 16777215)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('autocrms');
	}

}
