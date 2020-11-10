<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductVariantGroupColumnToDealsPromotionBuyxgetyProductRequirementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_buyxgety_rules', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_product_variant_group')->after('benefit_id_product')->nullable()->index('fk_deals_promotion_buyxgety_rules_product_variant_group');
        	$table->foreign('id_product_variant_group', 'fk_deals_promotion_buyxgety_rules_product_variant_group')->references('id_product_variant_group')->on('product_variant_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_promotion_buyxgety_rules', function (Blueprint $table) {
        	$table->dropForeign('fk_deals_promotion_buyxgety_rules_product_variant_group');
        	$table->dropColumn('id_product_variant_group');
        });
    }
}
