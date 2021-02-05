<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignOutletGroupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_outlet_groups', function (Blueprint $table) {
            $table->bigIncrements('id_promo_campaign_outlet_group');
            $table->unsignedInteger('id_promo_campaign');
            $table->unsignedInteger('id_outlet_group');
            $table->timestamps();

            $table->foreign('id_promo_campaign', 'fk_promo_campaign_outlet_groups_promo_campaign')->references('id_promo_campaign')->on('promo_campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet_group', 'fk_promo_campaign_outlet_groups_outlet_groups')->references('id_outlet_group')->on('outlet_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_outlet_groups');
    }
}
