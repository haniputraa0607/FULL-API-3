<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWhereInToCampaignRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_campaign_rules_campaign_rule_parents');
        });
        DB::connection('mysql')->statement("ALTER TABLE `campaign_rules` CHANGE COLUMN `id_campaign_rule_parent` `id_campaign_rule_parent` INT(10) UNSIGNED NOT NULL ,CHANGE COLUMN `campaign_rule_operator` `campaign_rule_operator` ENUM('=', 'like', '>', '<', '>=', '<=', 'WHERE IN') COLLATE 'utf8mb4_unicode_ci' NOT NULL ;");
        Schema::table('campaign_rules', function(Blueprint $table)
        {
            $table->foreign('id_campaign_rule_parent', 'fk_campaign_rules_campaign_rule_parents')->references('id_campaign_rule_parent')->on('campaign_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_campaign_rules_campaign_rule_parents');
        });
        DB::connection('mysql')->statement("ALTER TABLE `campaign_rules` CHANGE COLUMN `campaign_rule_operator` `campaign_rule_operator` ENUM('=', 'like', '>', '<', '>=', '<=') COLLATE 'utf8mb4_unicode_ci' NOT NULL ;");
        Schema::table('campaign_rules', function(Blueprint $table)
        {
            $table->foreign('id_campaign_rule_parent', 'fk_campaign_rules_campaign_rule_parents')->references('id_campaign_rule_parent')->on('campaign_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
