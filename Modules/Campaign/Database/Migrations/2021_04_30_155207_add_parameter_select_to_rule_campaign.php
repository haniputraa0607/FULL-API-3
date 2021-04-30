<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParameterSelectToRuleCampaign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_rules', function (Blueprint $table) {
            $table->string('campaign_rule_param_select')->nullable()->after('campaign_rule_param');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_rules', function (Blueprint $table) {
            $table->dropColumn('campaign_rule_param_select');
        });
    }
}
