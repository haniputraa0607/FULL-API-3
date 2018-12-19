<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInboxGlobalRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inbox_global_rule_parents', function (Blueprint $table) {
            $table->increments('id_inbox_global_rule_parent');
            $table->unsignedInteger('id_inbox_global');
            $table->enum('inbox_global_rule', ['and', 'or']);
            $table->enum('inbox_global_rule_next', ['and', 'or']);
            $table->timestamps();

            $table->foreign('id_inbox_global', 'fk_inbox_global_rule_parents_inbox_globals')->references('id_inbox_global')->on('inbox_globals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inbox_global_rule_parents');
    }
}
