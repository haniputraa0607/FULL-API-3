<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignSmsSentsTable extends Migration {

	public function up()
	{
		Schema::create('campaign_sms_sents', function(Blueprint $table)
		{
			$table->increments('id_campaign_sms_sent');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_sms_sents_campaigns');
			$table->string('sms_sent_to', 18);
			$table->text('sms_sent_content', 65535);
			$table->dateTime('sms_sent_send_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaign_sms_sents');
	}

}
