<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePromoTypeToDefaultToNullOnDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::table('deals_promotion_templates', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals_promotion_templates CHANGE COLUMN deals_promo_id_type deals_promo_id_type ENUM('promoid', 'nominal') DEFAULT NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals_promotion_templates CHANGE COLUMN deals_promo_id_type deals_promo_id_type ENUM('promoid', 'nominal') DEFAULT 'promoid'");
        });
    }
}
