<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_referral_codes', function (Blueprint $table) {
            $table->increments('id_user_referral');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_promo_campaign_promo_code');
            $table->timestamps();

            $table->foreign('id_promo_campaign_promo_code', 'fk_id_promo_campaign_promo_code_promo_campaign_promo_code')->references('id_promo_campaign_promo_code')->on('promo_campaign_promo_codes')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_user', 'fk_id_user_user_referrals_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_referral_codes');
    }
}
