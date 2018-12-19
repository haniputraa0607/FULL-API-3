<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCampaignRulesTable extends Migration {

	public function up()
	{
		Schema::create('campaign_rules', function(Blueprint $table)
		{
			$table->increments('id_campaign_rule');
			$table->integer('id_campaign')->unsigned()->index('fk_campaign_rules_campaigns');
			$table->string('campaign_rule_subject', 191);
			$table->enum('campaign_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('campaign_rule_param', 191);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('campaign_rules');
	}

}
