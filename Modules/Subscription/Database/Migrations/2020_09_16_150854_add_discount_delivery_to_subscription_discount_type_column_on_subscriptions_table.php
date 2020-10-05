<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountDeliveryToSubscriptionDiscountTypeColumnOnSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	\DB::statement("ALTER TABLE `subscriptions` CHANGE COLUMN `subscription_discount_type` `subscription_discount_type` ENUM('payment_method','discount','discount_delivery')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	\DB::statement("ALTER TABLE `subscriptions` CHANGE COLUMN `subscription_discount_type` `subscription_discount_type` ENUM('payment_method','discount')");
    }
}
