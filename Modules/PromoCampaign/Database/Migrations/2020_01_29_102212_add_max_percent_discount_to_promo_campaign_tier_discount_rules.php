<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxPercentDiscountToPromoCampaignTierDiscountRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_tier_discount_rules', function (Blueprint $table) {
        	$table->integer('max_percent_discount')->nullable()->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_tier_discount_rules', function (Blueprint $table) {
        	$table->dropColumn('max_percent_discount');
        });
    }
}
