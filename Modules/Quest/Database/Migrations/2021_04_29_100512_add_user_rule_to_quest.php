<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserRuleToQuest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->string('user_rule_operator')->nullable()->after('description');
            $table->string('user_rule_parameter')->nullable()->after('description');
            $table->string('user_rule_subject')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn('user_rule_operator');
            $table->dropColumn('user_rule_parameter');
            $table->dropColumn('user_rule_subject');
        });
    }
}
