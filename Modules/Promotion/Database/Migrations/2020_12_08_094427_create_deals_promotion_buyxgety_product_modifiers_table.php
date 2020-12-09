<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionBuyxgetyProductModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_buyxgety_product_modifiers', function (Blueprint $table) {
            $table->increments('id_deals_promotion_buyxgety_product_modifier');
            $table->unsignedInteger('id_deals_buyxgety_rule');
            $table->unsignedInteger('id_product_modifier')->index('deals_promotion_bxgy_modifier_id_product_modifier_group');
            $table->timestamps();

            $table->foreign('id_deals_buyxgety_rule', 'fk_deals_promotion_bxgy_modifier_bxgy_rule')->references('id_deals_buyxgety_rule')->on('deals_promotion_buyxgety_rules')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_buyxgety_product_modifiers');
    }
}
