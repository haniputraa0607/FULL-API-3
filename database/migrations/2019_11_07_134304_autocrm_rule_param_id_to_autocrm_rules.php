<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AutocrmRuleParamIdToAutocrmRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autocrm_rules', function (Blueprint $table) {
            $table->integer('autocrm_rule_param_id')->nullable()->after('autocrm_rule_param');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrm_rules', function (Blueprint $table) {
            $table->dropColumn('autocrm_rule_param_id');
        });
    }
}
