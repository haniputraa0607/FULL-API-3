<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferrerReferredBonusToPromoCampaignReferralTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_referral_transactions', function (Blueprint $table) {
            $table->enum('referred_bonus_type',['Product Discount','Cashback'])->default('cashback')->after('id_transaction');
            $table->unsignedInteger('referred_bonus')->default(0)->after('referred_bonus_type');
            $table->unsignedInteger('referrer_bonus')->default(0)->after('referred_bonus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaign_referral_transactions', function (Blueprint $table) {
            $table->dropColumn('referred_bonus_type');
            $table->dropColumn('referred_bonus');
            $table->dropColumn('referrer_bonus');
        });
    }
}
