<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCampaignPushSentsTable extends Migration {

	public function up()
	{
		Schema::table('campaign_push_sents', function(Blueprint $table)
		{
			$table->foreign('id_campaign', 'fk_campaign_push_sents_campaigns')->references('id_campaign')->on('campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('campaign_push_sents', function(Blueprint $table)
		{
			$table->dropForeign('fk_campaign_push_sents_campaigns');
		});
	}

}
