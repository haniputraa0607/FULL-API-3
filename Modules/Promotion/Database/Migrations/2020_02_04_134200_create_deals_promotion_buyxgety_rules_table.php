<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionBuyxgetyRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_buyxgety_rules', function (Blueprint $table) {

            $table->increments('id_deals_buyxgety_rule');
            $table->unsignedInteger('id_deals');
            $table->integer('min_qty_requirement');
            $table->integer('max_qty_requirement');
            $table->unsignedInteger('benefit_id_product');
            $table->integer('benefit_qty');
            $table->enum('discount_type', ['percent', 'nominal']);
            $table->integer('discount_value');
            $table->integer('max_percent_discount')->nullable();
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_promotion_buyxgety_rules_deals')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('benefit_id_product', 'fk_deals_promotion_buyxgety_rules_benefit_id_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_buyxgety_rules');
    }
}
