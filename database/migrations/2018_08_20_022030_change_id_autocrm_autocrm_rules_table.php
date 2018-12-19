<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdAutocrmAutocrmRulesTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('autocrm_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_autocrm_rules_autocrms');
            $table->dropColumn('id_autocrm');

            $table->unsignedInteger('id_autocrm_rule_parent')->nullable()->after('id_autocrm_rule');
            $table->foreign('id_autocrm_rule_parent', 'fk_autocrm_rules_autocrm_rule_parents')->references('id_autocrm_rule_parent')->on('autocrm_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('autocrm_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_autocrm_rules_autocrm_rule_parents');
            $table->dropColumn('id_autocrm_rule_parent');

            $table->unsignedInteger('id_autocrm')->after('id_autocrm_rule');
            $table->foreign('id_autocrm', 'fk_autocrm_rules_autocrms')->references('id_autocrm')->on('autocrms')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
