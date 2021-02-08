<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrateOutletGroupFilterConditionParents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_group_filter_condition_parents', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_group_filter_condition_parent');
            $table->unsignedInteger('id_outlet_group');
            $table->enum('condition_parent_rule', ['and', 'or'])->default('and');
            $table->enum('condition_parent_rule_next', ['and', 'or'])->default('and');
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
        //
    }
}
