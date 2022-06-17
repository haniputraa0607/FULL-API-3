<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToHairstylistAnnouncementRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_announcement_rule_parents', function (Blueprint $table) {
        	$table->foreign('id_hairstylist_announcement', 'fk_hs_announcement_rule_parents_hs_announcements')->references('id_hairstylist_announcement')->on('hairstylist_announcements')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_announcement_rule_parents', function (Blueprint $table) {
        	$table->dropForeign('fk_hs_announcement_rule_parents_hs_announcements');
        });
    }
}
