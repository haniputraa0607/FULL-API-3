<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewProductTypeToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	\DB::statement("ALTER TABLE `promo_campaigns` CHANGE COLUMN `product_type` `product_type` ENUM('single','variant','single + variant')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `promo_campaigns` CHANGE COLUMN `product_type` `product_type` ENUM('single','variant')");
    }
}
