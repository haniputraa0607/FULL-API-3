<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAutocrmRulesTable extends Migration {

	public function up()
	{
		Schema::create('autocrm_rules', function(Blueprint $table)
		{
			$table->increments('id_campaign_rule');
			$table->integer('id_autocrm')->unsigned()->index('fk_autocrm_rules_autocrms');
			$table->string('campaign_rule_subject', 191);
			$table->enum('campaign_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('campaign_rule_param', 191);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('autocrm_rules');
	}

}
