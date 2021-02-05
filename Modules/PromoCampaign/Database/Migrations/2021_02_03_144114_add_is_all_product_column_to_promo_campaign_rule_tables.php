<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAllProductColumnToPromoCampaignRuleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_tier_discount_rules', function (Blueprint $table) {
        	$table->boolean('is_all_product')->nullable()->after('max_percent_discount')->default(0);
        });

        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
        	$table->boolean('is_all_product')->nullable()->after('max_percent_discount')->default(0);
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
        	$table->dropColumn('is_all_product');
        });

        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
        	$table->dropColumn('is_all_product');
        });
    }
}
