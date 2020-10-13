<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignDiscountBillRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_discount_bill_rules', function (Blueprint $table) {
        	$table->increments('id_promo_campaign_discount_bill_rule');
            $table->unsignedInteger('id_promo_campaign');
            $table->enum('discount_type', ['Percent', 'Nominal']);
            $table->integer('discount_value');
            $table->integer('max_percent_discount')->nullable();
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_discount_bill_rules_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_discount_bill_rules');
    }
}
