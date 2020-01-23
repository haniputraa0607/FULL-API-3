<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdPromoCampaignToPromoCampaignUserFilters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaign_user_filters', function (Blueprint $table) {
            $table->unsignedInteger('id_promo_campaign');

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_user_filters_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {	
        Schema::table('promo_campaign_user_filters', function (Blueprint $table) { 
			$table->dropForeign('fk_promo_campaign_user_filters_promo_campaign');
			$table->dropColumn('id_promo_campaign');
		});
    }
}
