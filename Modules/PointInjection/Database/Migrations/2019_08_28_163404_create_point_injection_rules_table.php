<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointInjectionRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_injection_rules', function (Blueprint $table) {
            $table->increments('id_point_injection_rule');
            $table->unsignedInteger('id_point_injection_rule_parent');
            $table->string('point_injection_rule_subject');
            $table->string('point_injection_rule_operator');
            $table->string('point_injection_rule_param');
            $table->integer('point_injection_rule_param_id')->nullable();
            $table->timestamps();

            $table->foreign('id_point_injection_rule_parent', 'fk_point_injection_rules_id_point_injection_rule_parent')->references('id_point_injection_rule_parent')->on('point_injection_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_injection_rules');
    }
}
