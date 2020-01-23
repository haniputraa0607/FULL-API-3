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
        DB::connection('mysql')->statement("ALTER TABLE `campaign_rules` CHANGE COLUMN `id_campaign_rule_parent` `id_campaign_rule_parent` INT(10) UNSIGNED NOT NULL ,CHANGE COLUMN `campaign_rule_operator` `campaign_rule_operator` ENUM('=', 'like', '>', '<', '>=', '<=', 'WHERE IN') COLLATE 'utf8mb4_unicode_ci' NOT NULL ;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql')->statement("ALTER TABLE `campaign_rules` CHANGE COLUMN `campaign_rule_operator` `campaign_rule_operator` ENUM('=', 'like', '>', '<', '>=', '<=') COLLATE 'utf8mb4_unicode_ci' NOT NULL ;");
    }
}
