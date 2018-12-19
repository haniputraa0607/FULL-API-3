<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EditFieldNameAutocrmRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autocrm_rules', function(Blueprint $table) {
            $table->dropColumn('id_campaign_rule');
            $table->dropColumn('campaign_rule_subject');
            $table->dropColumn('campaign_rule_operator');
            $table->dropColumn('campaign_rule_param');
        });

        Schema::table('autocrm_rules', function(Blueprint $table) {
            $table->increments('id_autocrm_rule');
            $table->string('autocrm_rule_subject', 191);
            $table->enum('autocrm_rule_operator', array('=','like','>','<','>=','<='));
            $table->string('autocrm_rule_param', 191);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrm_rules', function(Blueprint $table) {
            $table->dropColumn('id_autocrm_rule');
            $table->dropColumn('autocrm_rule_subject');
            $table->dropColumn('autocrm_rule_operator');
            $table->dropColumn('autocrm_rule_param');
        });
        
        Schema::table('autocrm_rules', function(Blueprint $table) {
            $table->increments('id_campaign_rule');
            $table->string('campaign_rule_subject', 191);
            $table->enum('campaign_rule_operator', array('=','like','>','<','>=','<='));
            $table->string('campaign_rule_param', 191);
        });
    }
}
