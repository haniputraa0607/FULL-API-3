<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutocrmRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autocrm_rule_parents', function (Blueprint $table) {
            $table->increments('id_autocrm_rule_parent');
            $table->unsignedInteger('id_autocrm');
            $table->enum('autocrm_rule', ['and', 'or']);
            $table->enum('autocrm_rule_next', ['and', 'or']);
            $table->timestamps();

            $table->foreign('id_autocrm', 'fk_autocrm_rule_parent_autocrm')->references('id_autocrm')->on('autocrms')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('autocrm_rule_parents');
    }
}
