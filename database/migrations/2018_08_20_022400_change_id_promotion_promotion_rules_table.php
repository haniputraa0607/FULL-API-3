<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdPromotionPromotionRulesTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        Schema::table('promotion_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_promotion_rules_promotions');
            $table->dropColumn('id_promotion');

            $table->unsignedInteger('id_promotion_rule_parent')->nullable()->after('id_promotion_rule');
            $table->foreign('id_promotion_rule_parent', 'fk_promotion_rules_promotion_rule_parents')->references('id_promotion_rule_parent')->on('promotion_rule_parents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('promotion_rules', function(Blueprint $table)
        {
            $table->dropForeign('fk_promotion_rules_promotion_rule_parents');
            $table->dropColumn('id_promotion_rule_parent');

            $table->unsignedInteger('id_promotion')->after('id_promotion_rule');
            $table->foreign('id_promotion', 'fk_promotion_rules_promotions')->references('id_promotion')->on('promotions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}