<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InboxGlobalRuleParamIdToInboxGlobalRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inbox_global_rules', function (Blueprint $table) {
            $table->integer('inbox_global_rule_param_id')->nullable()->after('inbox_global_rule_param');
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
            $table->dropColumn('inbox_global_rule_param_id');
        });
    }
}
