<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignBuyxgetyProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_buyxgety_product_modifiers', function (Blueprint $table) {
            $table->increments('id_promo_campaign_buyxgety_product_modifier');
            $table->unsignedInteger('id_promo_campaign_buyxgety_rule');
            $table->unsignedInteger('id_product_modifier')->index('promo_campaign_bxgy_modifier_id_product_modifier_group');
            $table->timestamps();

            $table->foreign('id_promo_campaign_buyxgety_rule', 'fk_promo_campaign_bxgy_modifier_bxgy_rule')->references('id_promo_campaign_buyxgety_rule')->on('promo_campaign_buyxgety_rules')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_buyxgety_product_modifiers');
    }
}
