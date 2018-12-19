<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignEmailQueuesTable extends Migration {

	public function up()
	{
		Schema::create('campaign_email_queues', function(Blueprint $table)
		{
			$table->increments('id_campaign_email_queue');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_email_queues_campaigns');
			$table->string('email_queue_to');
			$table->string('email_queue_subject');
			$table->text('email_queue_content', 16777215);
			$table->dateTime('email_queue_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaign_email_queues');
	}

}
