<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandToSomePromoCampaignProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_buyxgety_product_requirements', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('id_product')->index();
        });

        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('benefit_id_product')->index();
        });

        Schema::table('promo_campaign_product_discounts', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('id_product')->index();
        });

        Schema::table('promo_campaign_tier_discount_products', function (Blueprint $table) {
        	$table->integer('id_brand')->nullable()->after('id_product')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_buyxgety_product_requirements', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });

        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });

        Schema::table('promo_campaign_product_discounts', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });

        Schema::table('promo_campaign_tier_discount_products', function (Blueprint $table) {
        	$table->dropColumn('id_brand');
        });
    }
}
