<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignBuyxgetyRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_buyxgety_rules', function (Blueprint $table) {
            $table->increments('id_promo_campaign_buyxgety_rule');
            $table->unsignedInteger('id_promo_campaign');
            $table->integer('min_qty_requirement');
            $table->integer('max_qty_requirement');
            $table->unsignedInteger('benefit_id_product');
            $table->integer('benefit_qty');
            $table->integer('discount_percent');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_buyxgety_rules_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('benefit_id_product', 'fk_promo_campaign_buyxgety_rules_benefit_id_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_buyxgety_rules');
    }
}
