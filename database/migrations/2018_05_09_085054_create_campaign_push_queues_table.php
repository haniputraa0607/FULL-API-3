<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignPushQueuesTable extends Migration {

	public function up()
	{
		Schema::create('campaign_push_queues', function(Blueprint $table)
		{
			$table->increments('id_campaign_push_queue');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_push_queues_campaigns');
			$table->string('push_queue_to');
			$table->string('push_queue_subject');
			$table->text('push_queue_content', 16777215)->nullable();
			$table->dateTime('push_queue_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaign_push_queues');
	}

}
