<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPromoDescriptionColumnToDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->text('promo_description')->nullable()->after('deals_description');
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
        	$table->dropColumn('promo_description');
        });
    }
}
