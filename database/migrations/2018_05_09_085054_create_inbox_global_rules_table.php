<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInboxGlobalRulesTable extends Migration {

	public function up()
	{
		Schema::create('inbox_global_rules', function(Blueprint $table)
		{
			$table->integer('id_inbox_global_rule')->unsigned();
			$table->integer('id_inbox_global')->unsigned()->index('fk_inbox_global_rules_inbox_globals');
			$table->string('inbox_rule_subject', 191);
			$table->enum('inbox_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('inbox_rule_param', 191);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('inbox_global_rules');
	}

}
