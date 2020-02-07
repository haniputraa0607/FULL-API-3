<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferredMinValueToPromoCampaignReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_referrals', function (Blueprint $table) {
            $table->unsignedInteger('referred_min_value')->after('referred_promo_value');
            $table->unsignedInteger('id_promo_campaign')->after('id_promo_campaign_referrals');
            $table->foreign('id_promo_campaign', 'fk_id_promo_campaign_promo_campaign_referral_promo_campaigns')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_referrals', function (Blueprint $table) {
            $table->dropForeign('fk_id_promo_campaign_promo_campaign_referral_promo_campaigns');
            $table->dropColumn('referred_min_value');
            $table->dropColumn('id_promo_campaign');
        });
    }
}
