<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_referrals', function (Blueprint $table) {
            $table->increments('id_promo_campaign_referrals');
            $table->enum('referred_promo_type',['Cashback','Product Discount']);
            $table->enum('referred_promo_unit',['Percent','Nominal']);
            $table->unsignedInteger('referred_promo_value');
            $table->unsignedInteger('referred_promo_value_max');
            $table->enum('referrer_promo_unit',['Percent','Nominal']);
            $table->unsignedInteger('referrer_promo_value');
            $table->unsignedInteger('referrer_promo_value_max');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_referrals');
    }
}
