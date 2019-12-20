<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignTierDiscountRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_tier_discount_rules', function (Blueprint $table) {
            $table->increments('id_promo_campaign_tier_discount_rule');
            $table->unsignedInteger('id_promo_campaign');
            $table->integer('min_qty');
            $table->integer('max_qty');
            $table->enum('discount_type', ['Percent', 'Nominal']);
            $table->integer('discount_value');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_tier_discount_rules_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_tier_discount_rules');
    }
}
