<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountDeliveryPromoTypeToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `promo_campaigns` CHANGE COLUMN `promo_type` `promo_type` ENUM('Product discount', 'Tier discount', 'Buy X Get Y', 'Referral', 'Discount bill', 'Discount delivery')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `promo_campaigns` CHANGE COLUMN `promo_type` `promo_type` ENUM('Product discount', 'Tier discount', 'Buy X Get Y', 'Referral', 'Discount bill')");
    }
}
