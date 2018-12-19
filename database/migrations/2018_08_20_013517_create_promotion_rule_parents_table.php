<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_rule_parents', function (Blueprint $table) {
            $table->increments('id_promotion_rule_parent');
            $table->unsignedInteger('id_promotion');
            $table->enum('promotion_rule', ['and', 'or']);
            $table->enum('promotion_rule_next', ['and', 'or']);
            $table->timestamps();

            $table->foreign('id_promotion', 'fk_promotion_rule_parent_promotion')->references('id_promotion')->on('promotions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promotion_rule_parents');
    }
}
