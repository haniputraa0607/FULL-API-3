<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInboxGlobalRulesTable extends Migration {

	public function up()
	{
		Schema::table('inbox_global_rules', function(Blueprint $table)
		{
			$table->foreign('id_inbox_global', 'fk_inbox_global_rules_inbox_globals')->references('id_inbox_global')->on('inbox_globals')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('inbox_global_rules', function(Blueprint $table)
		{
			$table->dropForeign('fk_inbox_global_rules_inbox_globals');
		});
	}

}
