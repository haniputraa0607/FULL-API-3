<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdDealsPromotionTemplatePromotionContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
     Schema::table('promotion_contents', function (Blueprint $table) {
            $table->unsignedInteger('id_deals_promotion_template')->nullable()->after('promotion_series_days');
            $table->foreign('id_deals_promotion_template', 'fk_promotion_contents_with_deals_promotion_templates')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
            $table->dropForeign('fk_promotion_contents_with_deals_promotion_templates');
            $table->dropColumn('id_deals_promotion_template');
        });
    }
}
