<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAllProductColumnToDealsPromotionRuleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_tier_discount_rules', function (Blueprint $table) {
        	$table->boolean('is_all_product')->nullable()->after('max_percent_discount')->default(0);
        });

        Schema::table('deals_promotion_buyxgety_rules', function (Blueprint $table) {
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
        Schema::table('deals_promotion_tier_discount_rules', function (Blueprint $table) {
        	$table->dropColumn('is_all_product');
        });

        Schema::table('deals_promotion_buyxgety_rules', function (Blueprint $table) {
        	$table->dropColumn('is_all_product');
        });
    }
}
