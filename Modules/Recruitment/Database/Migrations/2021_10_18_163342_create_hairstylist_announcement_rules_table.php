<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistAnnouncementRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_announcement_rules', function (Blueprint $table) {
            $table->increments('id_hairstylist_announcement_rule');
			$table->integer('id_hairstylist_announcement_rule_parent')->unsigned()->nullable()->index('fk_hs_announcement_rules_hs_announcement_rule_parents');
			$table->string('hairstylist_announcement_rule_subject', 191);
			$table->enum('hairstylist_announcement_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('hairstylist_announcement_rule_param', 191);
			$table->string('hairstylist_announcement_rule_param_select', 191)->nullable();
			$table->integer('hairstylist_announcement_rule_param_id')->nullable();
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
        Schema::dropIfExists('hairstylist_announcement_rules');
    }
}
