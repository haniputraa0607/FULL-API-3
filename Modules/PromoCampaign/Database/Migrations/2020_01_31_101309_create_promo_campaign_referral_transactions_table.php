<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignReferralTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_referral_transactions', function (Blueprint $table) {
            $table->bigIncrements('id_promo_campaign_referral_transaction');
            $table->unsignedInteger('id_promo_campaign_promo_code');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_transaction');
            $table->timestamps();

            $table->foreign('id_promo_campaign_promo_code', 'fk_id_promo_campaign_promo_code_campaign_promo_code')->references('id_promo_campaign_promo_code')->on('promo_campaign_promo_codes')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_id_user_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_transaction', 'fk_id_transaction_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_referral_transactions');
    }
}
