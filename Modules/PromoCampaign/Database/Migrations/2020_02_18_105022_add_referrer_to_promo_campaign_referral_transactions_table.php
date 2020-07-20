<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferrerToPromoCampaignReferralTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_referral_transactions', function (Blueprint $table) {
            $table->unsignedInteger('id_referrer')->after('id_user');
            $table->foreign('id_referrer', 'fk_id_referrer_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
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
            $table->dropForeign('fk_id_referrer_users');
            $table->dropColumn('id_referrer');
        });
    }
}
