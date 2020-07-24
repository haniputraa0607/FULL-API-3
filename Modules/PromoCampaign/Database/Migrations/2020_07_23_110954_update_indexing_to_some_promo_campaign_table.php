<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateIndexingToSomePromoCampaignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->index('prefix_code');
        	$table->index('is_all_outlet');
        	$table->index('used_code');
        	$table->index('promo_type');
        	$table->index('date_start');
        	$table->index('date_end');
        	$table->index('campaign_name');
        	$table->index('code_type');
        });

        Schema::table('promo_campaign_tags', function (Blueprint $table) {
        	$table->index('tag_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->dropIndex(['prefix_code']);
        	$table->dropIndex(['is_all_outlet']);
        	$table->dropIndex(['used_code']);
        	$table->dropIndex(['promo_type']);
        	$table->dropIndex(['date_start']);
        	$table->dropIndex(['date_end']);
        	$table->dropIndex(['campaign_name']);
        	$table->dropIndex(['code_type']);
        });

        Schema::table('promo_campaign_tags', function (Blueprint $table) {
        	$table->dropIndex(['tag_name']);
        });
    }
}
