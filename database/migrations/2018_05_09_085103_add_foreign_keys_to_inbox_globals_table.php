<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInboxGlobalsTable extends Migration {

	public function up()
	{
		Schema::table('inbox_globals', function(Blueprint $table)
		{
			$table->foreign('id_campaign', 'fk_inbox_globals_campaigns')->references('id_campaign')->on('campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('inbox_globals', function(Blueprint $table)
		{
			$table->dropForeign('fk_inbox_globals_campaigns');
		});
	}

}
