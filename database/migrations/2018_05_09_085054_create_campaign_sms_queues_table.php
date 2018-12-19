<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignSmsQueuesTable extends Migration {

	public function up()
	{
		Schema::create('campaign_sms_queues', function(Blueprint $table)
		{
			$table->increments('id_campaign_sms_queue');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_sms_queues_campaigns');
			$table->string('sms_queue_to', 18);
			$table->text('sms_queue_content', 65535);
			$table->dateTime('sms_queue_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaign_sms_queues');
	}

}
