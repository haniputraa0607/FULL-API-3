<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignHaveTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_have_tags', function (Blueprint $table) {
            $table->increments('id_promo_campaign_have_tag');
            $table->unsignedInteger('id_promo_campaign_tag');
            $table->unsignedInteger('id_promo_campaign');
            $table->timestamps();

            $table->foreign('id_promo_campaign_tag', 'fk_promo_campaign_have_tags_promo_campaign_tag')->references('id_promo_campaign_tag')->on('promo_campaign_tags')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_promo_campaign', 'fk_promo_campaign_have_tags_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_have_tags');
    }
}
