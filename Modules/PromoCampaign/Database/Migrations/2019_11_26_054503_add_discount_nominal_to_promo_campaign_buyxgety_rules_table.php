<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountNominalToPromoCampaignBuyxgetyRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_buyxgety_rules', function (Blueprint $table) {
            $table->integer('discount_percent')->nullable()->change();
            $table->integer('discount_nominal')->nullable()->after('discount_percent');
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
            $table->integer('discount_percent')->nullable(false)->change();
            $table->dropColumn('discount_nominal');
        });
    }
}
