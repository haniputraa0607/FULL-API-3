<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignProductDiscountRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_product_discount_rules', function (Blueprint $table) {
            $table->increments('id_promo_campaign_product_discount_rule');
            $table->unsignedInteger('id_promo_campaign');
            $table->char('is_all_product', 1);
            $table->enum('discount_type', ['Percent', 'Nominal']);
            $table->integer('discount_value');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_product_discount_rules_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_product_discount_rules');
    }
}
