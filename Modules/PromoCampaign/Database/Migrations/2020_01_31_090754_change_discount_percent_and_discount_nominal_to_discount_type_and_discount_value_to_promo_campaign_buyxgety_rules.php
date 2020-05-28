<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDiscountPercentAndDiscountNominalToDiscountTypeAndDiscountValueToPromoCampaignBuyxgetyRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'nominal'])->after('benefit_qty');
            $table->integer('discount_value')->after('discount_type');
            $table->dropColumn('discount_nominal');
            $table->dropColumn('discount_percent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
            $table->dropColumn('discount_type');
            $table->dropColumn('discount_value');
            $table->integer('discount_percent')->nullable()->after('benefit_qty');
            $table->integer('discount_nominal')->nullable()->after('discount_percent');
        });
    }
}
