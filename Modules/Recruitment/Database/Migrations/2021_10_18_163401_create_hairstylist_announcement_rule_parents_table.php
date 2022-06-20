<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistAnnouncementRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_announcement_rule_parents', function (Blueprint $table) {
            $table->increments('id_hairstylist_announcement_rule_parent');
			$table->bigInteger('id_hairstylist_announcement')->unsigned()->index('fk_hs_announcement_rule_parents_hs_announcements');
			$table->enum('hairstylist_announcement_rule', array('and','or'));
			$table->enum('hairstylist_announcement_rule_next', array('and','or'));
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_announcement_rule_parents');
    }
}
