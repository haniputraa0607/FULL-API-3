<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignPushSentsTable extends Migration {

	public function up()
	{
		Schema::create('campaign_push_sents', function(Blueprint $table)
		{
			$table->increments('id_campaign_push_sent');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_push_sents_campaigns');
			$table->string('push_sent_to');
			$table->string('push_sent_subject');
			$table->text('push_sent_content', 16777215)->nullable();
			$table->dateTime('push_sent_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaign_push_sents');
	}

}
