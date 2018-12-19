<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInboxGlobalRuleOperatorInboxGlobalRuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inbox_global_rules', function (Blueprint $table) {
            $table->dropColumn('inbox_rule_subject');
            $table->dropColumn('inbox_rule_operator');
            $table->dropColumn('inbox_rule_param');
			 
			$table->string('inbox_global_rule_subject', 191)->after('id_inbox_global_rule_parent');
			$table->enum('inbox_global_rule_operator', array('=','like','>','<','>=','<='))->after('inbox_global_rule_subject');
			$table->string('inbox_global_rule_param', 191)->after('inbox_global_rule_operator');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('inbox_global_rules', function (Blueprint $table) {
            $table->dropColumn('inbox_global_rule_subject');
            $table->dropColumn('inbox_global_rule_operator');
            $table->dropColumn('inbox_global_rule_param');
			 
			$table->string('inbox_rule_subject', 191);
			$table->enum('inbox_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('inbox_rule_param', 191);
        });
	}
}
