<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_rule_parents', function (Blueprint $table) {
            $table->increments('id_campaign_rule_parent');
            $table->unsignedInteger('id_campaign');
            $table->enum('campaign_rule', ['and', 'or']);
            $table->enum('campaign_rule_next', ['and', 'or']);
            $table->timestamps();

            $table->foreign('id_campaign', 'fk_campaign_rule_parent_campaign')->references('id_campaign')->on('campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_rule_parents');
    }
}
