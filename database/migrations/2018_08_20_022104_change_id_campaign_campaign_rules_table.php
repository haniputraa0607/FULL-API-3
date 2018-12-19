<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdCampaignCampaignRulesTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('campaign_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_campaign_rules_campaigns');
            $table->dropColumn('id_campaign');

            $table->unsignedInteger('id_campaign_rule_parent')->nullable()->after('id_campaign_rule');
            $table->foreign('id_campaign_rule_parent', 'fk_campaign_rules_campaign_rule_parents')->references('id_campaign_rule_parent')->on('campaign_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('campaign_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_campaign_rules_campaign_rule_parents');
            $table->dropColumn('id_campaign_rule_parent');

            $table->unsignedInteger('id_campaign')->after('id_campaign_rule');
            $table->foreign('id_campaign', 'fk_campaign_rules_campaigns')->references('id_campaign')->on('campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
