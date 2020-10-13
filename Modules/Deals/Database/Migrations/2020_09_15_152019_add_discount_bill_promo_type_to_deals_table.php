<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountBillPromoTypeToDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `deals` CHANGE COLUMN `promo_type` `promo_type` ENUM('Product discount', 'Tier discount', 'Buy X Get Y', 'Discount bill')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `deals` CHANGE COLUMN `promo_type` `promo_type` ENUM('Product discount', 'Tier discount', 'Buy X Get Y')");
    }
}
