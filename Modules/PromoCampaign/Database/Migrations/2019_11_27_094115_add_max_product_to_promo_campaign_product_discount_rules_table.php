<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxProductToPromoCampaignProductDiscountRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_product_discount_rules', function (Blueprint $table) {
            $table->integer('max_product')->default(0)->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_product_discount_rules', function (Blueprint $table) {
            $table->dropColumn('max_product');
        });
    }
}
