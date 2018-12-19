<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdInboxGlobalInboxGlobalRulesTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('inbox_global_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_inbox_global_rules_inbox_globals');
            $table->dropColumn('id_inbox_global');

            $table->unsignedInteger('id_inbox_global_rule_parent')->nullable()->after('id_inbox_global_rule');
            $table->foreign('id_inbox_global_rule_parent', 'fk_inbox_global_rules_inbox_global_rule_parents')->references('id_inbox_global_rule_parent')->on('inbox_global_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('inbox_global_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_inbox_global_rules_inbox_global_rule_parents');
            $table->dropColumn('id_inbox_global_rule_parent');

            $table->unsignedInteger('id_inbox_global')->after('id_inbox_global_rule');
            $table->foreign('id_inbox_global', 'fk_inbox_global_rules_inbox_globals')->references('id_inbox_global')->on('inbox_globals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}