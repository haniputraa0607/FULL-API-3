<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MergeUserReferralCodesAndUserReferralCashbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('user_referral_cashbacks');
        Schema::table('user_referral_codes', function (Blueprint $table) {
            $table->dropColumn('id_user_referral');
            $table->primary('id_user');
            $table->unsignedInteger('number_transaction')->after('id_promo_campaign_promo_code')->default(0);
            $table->unsignedInteger('cashback_earned')->after('number_transaction')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_referral_codes', function (Blueprint $table) {
            $table->dropForeign('fk_id_user_user_referrals_users');
            $table->dropPrimary('id_user');
            $table->foreign('id_user', 'fk_id_user_user_referrals_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
        Schema::table('user_referral_codes', function (Blueprint $table) {
            $table->increments('id_user_referral')->first();
            $table->dropColumn('number_transaction');
            $table->dropColumn('cashback_earned');
        });
        Schema::create('user_referral_cashbacks', function (Blueprint $table) {
            $table->increments('id_user_referral_cashback');
            $table->unsignedInteger('id_user');
            $table->string('referral_code');
            $table->unsignedInteger('number_transaction');
            $table->unsignedInteger('cashback_earned');
            $table->timestamps();
            $table->foreign('id_user', 'fk_id_user_user_referral_cashbacks_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
