<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInboxGlobalsTable extends Migration {

	public function up()
	{
		Schema::create('inbox_globals', function(Blueprint $table)
		{
			$table->increments('id_inbox_global');
			$table->integer('id_campaign')->unsigned()->nullable()->index('fk_inbox_globals_campaigns');
			$table->string('inbox_global_subject');
			$table->text('inbox_global_content', 16777215)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('inbox_globals');
	}

}
