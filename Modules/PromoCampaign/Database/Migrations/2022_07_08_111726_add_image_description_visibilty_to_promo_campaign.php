<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImageDescriptionVisibiltyToPromoCampaign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
            $table->enum('promo_campaign_visibility', ['Visible', 'Hidden'])->nullable()->default('Visible')->after('promo_title');
            $table->string('promo_image_detail', 200)->nullable()->after('promo_title');
            $table->string('promo_image', 200)->nullable()->after('promo_title');
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
            $table->dropColumn('promo_campaign_visibility');
            $table->dropColumn('promo_image_detail');
            $table->dropColumn('promo_image');
        });
    }
}
