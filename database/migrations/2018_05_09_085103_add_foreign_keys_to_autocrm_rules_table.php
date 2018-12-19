<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAutocrmRulesTable extends Migration {

	public function up()
	{
		Schema::table('autocrm_rules', function(Blueprint $table)
		{
			$table->foreign('id_autocrm', 'fk_autocrm_rules_autocrms')->references('id_autocrm')->on('autocrms')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('autocrm_rules', function(Blueprint $table)
		{
			$table->dropForeign('fk_autocrm_rules_autocrms');
		});
	}

}
