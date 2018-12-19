<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->increments('id_promotion_rule');
			$table->integer('id_promotion')->unsigned()->index('fk_promotion_rules_promotions');
			$table->string('promotion_rule_subject', 191);
			$table->enum('promotion_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('promotion_rule_param', 191);
			$table->timestamps();
        });
		
		Schema::table('promotion_rules', function (Blueprint $table) {
			$table->foreign('id_promotion', 'fk_promotion_rules_promotions')->references('id_promotion')->on('promotions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('promotion_rules', function (Blueprint $table) {
			$table->dropForeign('fk_promotion_rules_promotions');
		});
        Schema::dropIfExists('promotion_rules');
    }
}
