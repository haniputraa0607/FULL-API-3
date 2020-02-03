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
            $table->dropColumn('referred_min_value');
        });
    }
}
