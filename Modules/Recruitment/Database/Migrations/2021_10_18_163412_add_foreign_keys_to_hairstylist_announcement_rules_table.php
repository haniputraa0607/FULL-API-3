<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToHairstylistAnnouncementRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_announcement_rules', function (Blueprint $table) {
        	$table->foreign('id_hairstylist_announcement_rule_parent', 'fk_hs_announcement_rules_hs_announcement_rule_parents')->references('id_hairstylist_announcement_rule_parent')->on('hairstylist_announcement_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_announcement_rules', function (Blueprint $table) {
        	$table->dropForeign('fk_hs_announcement_rules_hs_announcement_rule_parents');
        });
    }
}
