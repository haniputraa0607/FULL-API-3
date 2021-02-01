<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewProductTypeToDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	\DB::statement("ALTER TABLE `deals_promotion_templates` CHANGE COLUMN `product_type` `product_type` ENUM('single','variant','single + variant')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	\DB::statement("ALTER TABLE `deals_promotion_templates` CHANGE COLUMN `product_type` `product_type` ENUM('single','variant')");
    }
}
