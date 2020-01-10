<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePointInjectionRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('point_injection_rule_parents', function (Blueprint $table) {
            $table->increments('id_point_injection_rule_parent');
            $table->unsignedInteger('id_point_injection');
            $table->string('point_injection_rule');
            $table->string('point_injection_rule_next');
            $table->timestamps();

            $table->foreign('id_point_injection', 'fk_point_injection_rule_parents_id_point_injection')->references('id_point_injection')->on('point_injections')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_injection_rule_parents');
    }
}
