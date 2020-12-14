<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductVariantGroupToAllPromoCampaignProductRequirementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_buyxgety_product_requirements', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_product_variant_group')->after('id_product_category')->nullable()->index('fk_promo_campaign_buyxgety_product_req_product_variant_group');
        });

        Schema::table('promo_campaign_discount_bill_products', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_product_variant_group')->after('id_product_category')->nullable()->index('fk_promo_campaign_buyxgety_product_req_product_variant_group');
        });

        Schema::table('promo_campaign_product_discounts', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_product_variant_group')->after('id_product_category')->nullable()->index('fk_promo_campaign_buyxgety_product_req_product_variant_group');
        });

        Schema::table('promo_campaign_tier_discount_products', function (Blueprint $table) {
        	$table->unsignedBigInteger('id_product_variant_group')->after('id_product_category')->nullable()->index('fk_promo_campaign_buyxgety_product_req_product_variant_group');
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
        	$table->dropColumn('id_product_variant_group');
        });

        Schema::table('promo_campaign_discount_bill_products', function (Blueprint $table) {
        	$table->dropColumn('id_product_variant_group');
        });

        Schema::table('promo_campaign_product_discounts', function (Blueprint $table) {
        	$table->dropColumn('id_product_variant_group');
        });

        Schema::table('promo_campaign_tier_discount_products', function (Blueprint $table) {
        	$table->dropColumn('id_product_variant_group');
        });
    }
}
